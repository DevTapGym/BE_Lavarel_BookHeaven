<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;
use App\Models\Cart;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;


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
                'name'      => $request->name,
                'email'     => $request->email,
                'is_active' => false,
                'password'  => bcrypt($request->password),
            ]);

            // Gán role cho user
            $user->assignRole('admin');

            // Tạo Customer tương ứng
            $customer = Customer::create([
                'name'    => $request->name,
                'email'   => $request->email,
                'phone'   => null, // Sẽ được cập nhật sau
                'address' => null, // Sẽ được cập nhật sau
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
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'is_active'   => $user->is_active,
                ],
            );
        });
    }

    public function me()
    {
        $user = Auth::user();

        return $this->successResponse(
            200,
            'Get user info successful',
            [
                'name'      => $user->name,
                'email'     => $user->email,
                'is_active' => $user->is_active,
            ]
        );
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
            return $this->errorResponse(401, 'Unauthorized', 'Refresh token not found');
        }

        try {
            $payload = JWTAuth::getJWTProvider()->decode($refreshToken);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return $this->errorResponse(401, 'Unauthorized', 'Invalid refresh token type');
            }

            $user = User::find($payload['sub']);
            if (!$user) {
                return $this->errorResponse(401, 'Unauthorized', 'User not found');
            }

            $role = $user->roles()->pluck('name')->first() ?? 'user';
            $customClaims = [
                'role' => $role,
                'is_active' => $user->is_active,
            ];

            $accessToken = JWTAuth::claims($customClaims)->fromUser($user);

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
                $this->formatAuthData($accessToken, $user, $newRefreshToken)
            )->cookie(
                'refresh_token',
                $newRefreshToken,
                60 * 24 * 14,
                null,
                null,
                true,
                true
            );
        } catch (Exception $e) {
            return $this->errorResponse(401, 'Unauthorized', 'Invalid or expired refresh token');
        }
    }

    protected function formatAuthData($token, $user)
    {
        $data = [
            'access_token' => $token,
            'expires_in'   => Auth::factory()->getTTL() * 60,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ]
        ];

        return $data;
    }
}
