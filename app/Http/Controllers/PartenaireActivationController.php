<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Activation d'un compte partenaire via un lien signé reçu par e-mail :
 * le partenaire choisit son mot de passe, ce qui confirme son compte et le connecte.
 */
class PartenaireActivationController extends Controller
{
    /** Page « définir mon mot de passe » (lien signé). */
    public function show(Request $request, User $user)
    {
        abort_unless($user->isPartenaire(), 404);

        return Inertia::render('Partenaire/Activation', [
            'name'  => $user->name,
            'email' => $user->email,
            // Conserve la signature pour la soumission.
            'query' => $request->getQueryString(),
        ]);
    }

    /** Enregistre le mot de passe, confirme le compte et connecte le partenaire. */
    public function store(Request $request, User $user)
    {
        abort_unless($user->isPartenaire(), 404);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password'          => Hash::make($request->string('password')),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portail.index')
            ->with('success', 'Compte activé. Bienvenue !');
    }
}
