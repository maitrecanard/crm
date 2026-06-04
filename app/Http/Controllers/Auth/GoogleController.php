<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /** Redirige vers Google. */
    public function redirect()
    {
        abort_unless(config('services.google.client_id'), 404);

        return Socialite::driver('google')->redirect();
    }

    /** Retour de Google : on connecte l'utilisateur correspondant. */
    public function callback(Request $request)
    {
        abort_unless(config('services.google.client_id'), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Connexion Google annulée ou échouée.']);
        }

        // Liaison par email (le compte CRM doit déjà exister).
        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => "Aucun compte CRM pour « {$googleUser->getEmail()} ». "
                    .'Demande à un administrateur de créer ton accès.',
            ]);
        }

        // google_id est chiffré (cast 'encrypted' avec APP_KEY).
        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ])->save();

        // Google est un fournisseur d'identité fort : on ne redemande pas le TOTP.
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
