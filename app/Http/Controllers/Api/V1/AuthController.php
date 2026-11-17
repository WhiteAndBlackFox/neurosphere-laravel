<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Not part of the original spec: Sanctum auth is required on every
 * endpoint, but no token-issuance route was defined. Added so the
 * documented smoke test can actually obtain a token.
 */
final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json(['data' => ['token' => $user->createToken('api')->plainTextToken]]);
    }
}
