<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($status, $message = 'Success', $data = null,)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function errorResponse($status, $error = null, $message = 'Error')
    {
        return response()->json([
            'status' => $status,
            'error' => $error,
            'message' => $message,
        ], $status);
    }
}
