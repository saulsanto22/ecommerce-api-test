<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registered successfully', ['user_id' => $user->id]);

            return $this->created([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'Registrasi berhasil');

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Registrasi gagal: '.$e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::where('email', $validated['email'])->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                return $this->error('Email atau password salah', 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in', ['user_id' => $user->id]);

            return $this->success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'Login berhasil');

        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Login gagal: '.$e->getMessage(), 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            Log::info('User logged out', ['user_id' => $request->user()->id]);

            return $this->success(null, 'Logout berhasil');

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Logout gagal: '.$e->getMessage(), 500);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $request->user(),
        ], 'Profile retrieved successfully');
    }
}
