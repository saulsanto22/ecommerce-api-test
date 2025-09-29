<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function created($data = null, string $message = 'Resource created successfully')
    {
        return $this->success($data, $message, 201);
    }

    protected function notFound(string $message = 'Resource not found')
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }
}
