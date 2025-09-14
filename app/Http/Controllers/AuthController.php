<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!$token = Auth::attempt($credentials)) {
            return $this->errorResponse(
                401,
                'Unauthorized',
                'Incorrect email or password',
            );
        }

        $user = Auth::user();
        $user->current_token = $token;

        $refreshToken = Str::random(64);
        $user->refresh_token = $refreshToken;
        $user->save();

        return $this->successResponse(
            200,
            'Login successful',
            $this->formatAuthData($token, $user, $refreshToken),
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
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'is_active' => false,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('admin');

        return $this->successResponse(
            201,
            'Register successful',
            [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        );
    }

    public function me()
    {
        return $this->successResponse(
            200,
            'Get user info successful',
            Auth::user(),
        );
    }

    public function logout()
    {
        $user = Auth::user();
        if ($user) {
            $user->refresh_token = null;
            $user->save();
        }
        Auth::logout();

        return $this->successResponse(
            200,
            'Successfully logged out',
            null
        );
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return $this->errorResponse(
                401,
                'Unauthorized',
                'Refresh token not found'
            );
        }

        $user = User::where('refresh_token', $refreshToken)->first();

        if (!$user) {
            return $this->errorResponse(
                401,
                'Unauthorized',
                'Invalid refresh token'
            );
        }

        $token = Auth::login($user);

        $newRefreshToken = Str::random(64);
        $user->refresh_token = $newRefreshToken;
        $user->save();

        return $this->successResponse(
            200,
            'Token refreshed',
            $this->formatAuthData($token, $user, $newRefreshToken)
        )->cookie(
            'refresh_token',
            $newRefreshToken,
            60 * 24 * 14,
            null,
            null,
            true,
            true
        );
    }


    protected function formatAuthData($token, $user)
    {
        $data = [
            'access_token' => $token,
            'expires_in'   => Auth::factory()->getTTL() * 60,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'is_active' => $user->is_active,
                'email' => $user->email,
            ]
        ];

        return $data;
    }
}
