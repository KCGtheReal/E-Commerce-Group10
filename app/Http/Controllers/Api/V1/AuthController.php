<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function register(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => trim((string) $validated['name']),
            'email' => $validated['email'],
            'phone' => trim((string) $validated['phone']),
            'password' => Hash::make((string) $validated['password']),
            'role' => UserRole::Customer,
        ]);

        $cartToken = $request->header('X-Cart-Token');
        $cart = $this->cartService->mergeGuestCartByToken(
            $user,
            is_string($cartToken) ? $cartToken : null
        );

        $issued = ApiToken::issueFor($user, (string) ($validated['device_name'] ?? 'mobile-app'));

        return response()->json([
            'token' => $issued['plainTextToken'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expiresAt']?->toIso8601String(),
            'user' => (new UserResource($user))->resolve($request),
            'cart' => (new CartResource($cart))->resolve($request),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = 'api-login:'.sha1($validated['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'success' => false,
                'message' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => (int) ceil($seconds / 60)]),
                'code' => 'RATE_LIMITED',
            ], 429);
        }

        $user = User::where('email', $validated['email'])->first();
        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'success' => false,
                'message' => trans('auth.failed'),
                'code' => 'VALIDATION_ERROR',
                'errors' => [
                    'email' => [trans('auth.failed')],
                ],
            ], 422);
        }

        RateLimiter::clear($throttleKey);

        $cartToken = $request->header('X-Cart-Token');
        $cart = $this->cartService->mergeGuestCartByToken(
            $user,
            is_string($cartToken) ? $cartToken : null
        );

        $issued = ApiToken::issueFor($user, (string) ($validated['device_name'] ?? 'mobile-app'));

        return response()->json([
            'token' => $issued['plainTextToken'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expiresAt']?->toIso8601String(),
            'user' => (new UserResource($user))->resolve($request),
            'cart' => (new CartResource($cart))->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('api_token');
        if ($apiToken instanceof ApiToken) {
            $apiToken->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $validated['email']]);

        return response()->json([
            'message' => __($status),
            'status' => $status,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => (new UserResource($request->user()))->resolve($request),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();
        if (! Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('validation.current_password'),
                'code' => 'VALIDATION_ERROR',
                'errors' => [
                    'current_password' => [__('validation.current_password')],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make((string) $validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
