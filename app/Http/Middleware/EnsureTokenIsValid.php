<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Traits\ApiResponse;

class EnsureTokenIsValid
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::parseToken();
            $user = $token->authenticate();
        } catch (JWTException $e) {
            return $this->errorResponse(
                401,
                'Unauthorized',
                'Token is invalid or not transmitted',
            );
        }

        return $next($request);
    }
}
