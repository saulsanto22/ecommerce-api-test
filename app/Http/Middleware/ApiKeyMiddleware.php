<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sesuai soal: accessKey & secretKey di header setiap request
        $accessKey = $request->header('X-ACCESS-KEY');
        $secretKey = $request->header('X-SECRET-KEY');

        $validAccessKey = config('app.api_access_key');
        $validSecretKey = config('app.api_secret_key');

        if (! $accessKey || ! $secretKey) {
            return response()->json([
                'success' => false,
                'message' => 'API credentials required',
            ], 401);
        }

        if ($accessKey !== $validAccessKey || $secretKey !== $validSecretKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API credentials',
            ], 401);
        }

        return $next($request);
    }
}
