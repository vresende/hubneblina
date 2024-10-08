<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();

        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now());

        foreach ($expiredTokens as $token) {
            $token->delete();
        }


        $token = $user->createToken('API Token',expiresAt: now()->addHour(1))->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        if (auth()->user()->email !== 'ti@grupomater.com.br') {
            return response()->json([
                'success' => false,
                'errors' => [
                    'message' => 'you are not allowed'
                ]
            ], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::find($request->email);
        if ($user) {
            return response()->json(['message' => 'User already exists'], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Criar o token com expiração de 1 hora
        $token = $user->createToken($request->device_name ?? 'default', ['*'], now()->addHour());

        return response()->json(array_merge($user->toArray(), ['token' => $token->plainTextToken]));
    }
}
