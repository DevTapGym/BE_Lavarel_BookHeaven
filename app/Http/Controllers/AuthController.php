<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;
use App\Models\Cart;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use App\Http\Resources\AccountResource;


class AuthController extends Controller
{

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse(
                401,
                'Unauthorized',
                'Incorrect email or password',
            );
        }

        $user = Auth::user();
        $role = $user->roles()->pluck('name')->first() ?? 'user';

        $customClaims = [
            'role' => $role,
            'is_active' => $user->is_active,
        ];

        $accessToken = JWTAuth::claims($customClaims)->fromUser($user);

        $refreshTokenPayload = [
            'sub' => $user->id,
            'jti' => Str::uuid()->toString(),
            'type' => 'refresh',
        ];

        $refreshToken = JWTAuth::getJWTProvider()->encode(
            array_merge(
                $refreshTokenPayload,
                [
                    'exp' => now()->addDays(14)->timestamp // 14 ngày
                ]
            )
        );

        // Lưu jti của access token vào database
        $accessPayload = JWTAuth::setToken($accessToken)->getPayload();
        $accessJti = $accessPayload->get('jti');
        $user->current_jti = $accessJti;
        $user->save();

        return $this->successResponse(
            200,
            'Login successful',
            $this->formatAuthData($accessToken, $user, $refreshToken),
        )->cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 14,
            null,
            null,
            true,
            true
        );
    }

    public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Tạo User
            $user = User::create([
                'name'      => $request->username,
                'password'  => bcrypt($request->password),
                'email'     => $request->email,
                'is_active' => $request->for === 'web' ? true : false,
            ]);

            // Gán role cho user
            $user->assignRole('CUSTOMER');

            // Tạo Customer tương ứng
            $customer = Customer::create([
                'name'    => $request->username,
                'email'   => $request->email,
                'phone'   => $request->phone,
                'address' => null, // Sẽ được cập nhật sau
                'gender' => 'Khác',
            ]);

            // Liên kết User với Customer (cần thêm customer_id vào bảng users)
            $user->update(['customer_id' => $customer->id]);

            // Tạo Cart cho Customer
            Cart::create([
                'customer_id' => $customer->id,
                'count'    => 0,
                'total_price' => 0,
            ]);

            return $this->successResponse(
                201,
                'Register successful',
                [
                    'id'     => $user->id,
                    'username'        => $user->name,
                    'email'       => $user->email,
                    'is_active'   => $user->is_active,
                    'createdAt' => $user->created_at,
                ],
            );
        });
    }

    public function loginWithGoogle(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email',
            'avatar' => 'nullable|string',
        ]);

        $email = $request->email;

        // Kiểm tra xem user đã tồn tại hay chưa
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if (empty($existingUser->avatar) && !empty($request->avatar)) {
                $existingUser->avatar = $request->avatar;
                $existingUser->save();
            }
            // Nếu user đã tồn tại → cho đăng nhập luôn
            $role = $existingUser->roles()->pluck('name')->first() ?? 'user';
            $customClaims = [
                'role' => $role,
                'is_active' => $existingUser->is_active,
            ];

            $accessToken = JWTAuth::claims($customClaims)->fromUser($existingUser);

            // Tạo refresh token mới
            $refreshTokenPayload = [
                'sub' => $existingUser->id,
                'jti' => Str::uuid()->toString(),
                'type' => 'refresh',
            ];

            $refreshToken = JWTAuth::getJWTProvider()->encode(
                array_merge(
                    $refreshTokenPayload,
                    ['exp' => now()->addDays(14)->timestamp]
                )
            );

            // Cập nhật jti mới
            $accessPayload = JWTAuth::setToken($accessToken)->getPayload();
            $accessJti = $accessPayload->get('jti');
            $existingUser->current_jti = $accessJti;
            $existingUser->save();

            return $this->successResponse(
                200,
                'Google login successful (existing user)',
                $this->formatAuthData($accessToken, $existingUser, $refreshToken)
            )->cookie(
                'refresh_token',
                $refreshToken,
                60 * 24 * 14,
                null,
                null,
                true,
                true
            );
        }

        // Nếu chưa có user → tạo mới toàn bộ
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name'       => $request->name,
                'email'      => $request->email,
                'avatar'     => $request->avatar,
                'password'   => bcrypt(Str::random(16)), // password ngẫu nhiên
                'is_active'  => true,
            ]);

            // Gán role mặc định (vd: user)
            $user->assignRole('CUSTOMER');

            // Tạo Customer tương ứng
            $customer = Customer::create([
                'name'    => $request->name,
                'email'   => $request->email,
                'phone'   => null,
                'address' => null,
                'gender'  => 'Khác',
            ]);

            // Liên kết User với Customer
            $user->update(['customer_id' => $customer->id]);

            // Tạo giỏ hàng cho Customer
            Cart::create([
                'customer_id' => $customer->id,
                'count'       => 0,
                'total_price' => 0,
            ]);

            // Tạo token
            $role = $user->roles()->pluck('name')->first() ?? 'user';
            $customClaims = [
                'role' => $role,
                'is_active' => $user->is_active,
            ];

            $accessToken = JWTAuth::claims($customClaims)->fromUser($user);

            $refreshTokenPayload = [
                'sub' => $user->id,
                'jti' => Str::uuid()->toString(),
                'type' => 'refresh',
            ];

            $refreshToken = JWTAuth::getJWTProvider()->encode(
                array_merge(
                    $refreshTokenPayload,
                    ['exp' => now()->addDays(14)->timestamp]
                )
            );

            $accessPayload = JWTAuth::setToken($accessToken)->getPayload();
            $accessJti = $accessPayload->get('jti');
            $user->current_jti = $accessJti;
            $user->save();

            return $this->successResponse(
                200,
                'Google login successful (new user)',
                $this->formatAuthData($accessToken, $user, $refreshToken)
            )->cookie(
                'refresh_token',
                $refreshToken,
                60 * 24 * 14,
                null,
                null,
                true,
                true
            );
        });
    }



    public function me()
    {
        $user = Auth::user();

        $user->load('customer');
        $role = $user->roles()->pluck('name')->first() ?? 'user';

        return $this->successResponse(
            200,
            'Get user info successful',
            [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->customer->phone ?? null,
                'avatar'    => $user->avatar,
                'gender'    => $user->customer->gender ?? null,
                'date_of_birth' => $user->customer->date_of_birth ?? null,
                'role'      => $user->roles()->pluck('name')->first() ?? 'user',
                'is_active' => $user->is_active,
            ]
        );
    }

    public function account()
    {
        $user = Auth::user();

        $user->load([
            'customer.cart.cartItems.book.bookImages',
            'customer.cart.cartItems.book.categories',
            'roles'
        ]);

        return $this->successResponse(
            200,
            'Get user info successful',
            ['account' => new AccountResource($user)]
        );
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse(
                    401,
                    'Unauthorized',
                    'User not authenticated'
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'date_of_birth' => 'sometimes|date|before:today',
                'phone' => 'sometimes|string|max:10|regex:/^0[0-9]{9,10}$/',
                'gender' => 'sometimes|in:Nam,Nữ,Khác',
            ]);

            return DB::transaction(function () use ($user, $validated) {
                // Cập nhật name trong bảng users
                if (isset($validated['name'])) {
                    $user->update(['name' => $validated['name']]);
                }

                // Cập nhật các trường còn lại trong bảng customers
                $customer = $user->customer;
                if (!$customer) {
                    return $this->errorResponse(
                        404,
                        'Not Found',
                        'Customer profile not found'
                    );
                }

                $customerData = [];
                if (isset($validated['name'])) {
                    $customerData['name'] = $validated['name'];
                }
                if (isset($validated['date_of_birth'])) {
                    $customerData['date_of_birth'] = $validated['date_of_birth'];
                }
                if (isset($validated['phone'])) {
                    $customerData['phone'] = $validated['phone'];
                }
                if (isset($validated['gender'])) {
                    $customerData['gender'] = $validated['gender'];
                }

                if (!empty($customerData)) {
                    $customer->update($customerData);
                }

                // Load lại quan hệ để lấy dữ liệu mới nhất
                $user->load('customer');

                return $this->successResponse(
                    200,
                    'Profile updated successfully',
                    [
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'is_active'     => $user->is_active,
                        'avatar'        => $user->avatar,
                        'date_of_birth' => $user->customer->date_of_birth ?? null,
                        'phone'         => $user->customer->phone ?? null,
                        'gender'        => $user->customer->gender ?? null,
                    ]
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal server error',
                'Could not update profile: ' . $e->getMessage()
            );
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse(
                    401,
                    'Unauthorized',
                    'User not authenticated'
                );
            }

            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string',
            ]);

            // Kiểm tra mật khẩu hiện tại có đúng không
            if (!password_verify($validated['current_password'], $user->password)) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'Current password is incorrect'
                );
            }

            // Kiểm tra mật khẩu mới có khác mật khẩu cũ không
            if (password_verify($validated['new_password'], $user->password)) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'New password must be different from current password'
                );
            }

            // Cập nhật mật khẩu mới
            $user->update([
                'password' => bcrypt($validated['new_password'])
            ]);

            // Vô hiệu hóa tất cả token hiện tại để buộc người dùng đăng nhập lại
            //$user->current_jti = null;
            $user->save();

            return $this->successResponse(
                200,
                'Password changed successfully',
                null
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal server error',
                'Could not change password: ' . $e->getMessage()
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if ($token) {
                JWTAuth::invalidate($token);
            }

            $user = Auth::user();
            if ($user) {
                $user->current_jti = null;
                $user->save();
            }

            Auth::logout();

            return response()->json([
                'status' => 200,
                'message' => 'Successfully logged out',
            ])->cookie(
                'refresh_token',
                '',
                -1,
                null,
                null,
                true,
                true
            );
        } catch (Exception $e) {
            return $this->errorResponse(500, 'Internal server error', 'Could not logout: ' . $e->getMessage());
        }
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return $this->errorResponse(400, 'Unauthorized', 'Refresh token not found');
        }

        try {
            $payload = JWTAuth::getJWTProvider()->decode($refreshToken);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return $this->errorResponse(400, 'Unauthorized', 'Invalid refresh token type');
            }

            $user = User::find($payload['sub']);
            if (!$user) {
                return $this->errorResponse(400, 'Unauthorized', 'User not found');
            }

            $role = $user->roles()->pluck('name')->first() ?? 'user';
            $customClaims = [
                'role' => $role,
                'is_active' => $user->is_active,
            ];

            $accessToken = JWTAuth::claims($customClaims)->fromUser($user);

            // Cập nhật current_jti với JTI của access token mới
            $accessPayload = JWTAuth::setToken($accessToken)->getPayload();
            $accessJti = $accessPayload->get('jti');
            $user->current_jti = $accessJti;
            $user->save();

            $newRefreshTokenPayload = [
                'sub' => $user->id,
                'jti' => Str::uuid()->toString(),
                'type' => 'refresh',
            ];
            $newRefreshToken = JWTAuth::getJWTProvider()->encode(
                array_merge(
                    $newRefreshTokenPayload,
                    [
                        'exp' => now()->addDays(14)->timestamp
                    ]
                )
            );

            return $this->successResponse(
                200,
                'Token refreshed',
                $this->formatAuthData($accessToken, $user)
            )->cookie(
                'refresh_token',
                $newRefreshToken,
                60 * 24 * 14,
                '/',
                'localhost',
                true,
                true
            );
        } catch (Exception $e) {
            return $this->errorResponse(400, 'Unauthorized', 'Invalid or expired refresh token');
        }
    }

    protected function formatAuthData($token, $user)
    {
        $user->load('customer');
        $data = [
            'account' => [
                'id'        => $user->id,
                'email'     => $user->email,
                'name'      => $user->name,
                'avatar'    => $user->avatar,
                'phone'     => $user->customer->phone ?? null,
                'role'     => $user->roles()->pluck('name')->first() ?? 'user',
                'customer' => $user->customer,
                'date_of_birth' => $user->customer->date_of_birth ?? null,
                'gender'    => $user->customer->gender ?? null,
                'is_active' => $user->is_active,
            ],
            'access_token' => $token,
        ];

        return $data;
    }
}
