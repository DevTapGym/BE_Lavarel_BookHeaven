<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class CheckPermissionByRoute
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $routeName = $request->route()->getName();
        $permissionName = str_replace('.', ' ', $routeName);

        if (!$user->can($permissionName)) {
            return $this->errorResponse(
                403,
                'Forbidden',
                'You don\'t have permission to access this endpoint.',
            );
        }

        return $next($request);
    }
}
