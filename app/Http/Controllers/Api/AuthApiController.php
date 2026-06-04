<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /** Échange email + mot de passe contre un token d'API (pour l'app mobile). */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'device'   => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        // ⚠ Un token mobile contourne le 2FA web : on le limite en abilities.
        $token = $user->createToken($data['device'] ?? 'mobile', ['crm:read', 'crm:write']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json(['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }
}
