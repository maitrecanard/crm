<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impose la double authentification : un utilisateur sans 2FA actif est renvoyé
 * vers son profil pour l'activer (sauf comptes Google, déjà protégés par Google).
 * N'enferme pas : profil, gestion 2FA et déconnexion restent accessibles.
 */
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (config('crm.require_2fa') && $user
            && ! $user->hasTwoFactorEnabled()
            && empty($user->google_id)
            && ! $request->routeIs('profile.*', 'two-factor.*', 'logout', 'password.confirm', 'password.update', 'verification.*')
        ) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Sécurité : activez la double authentification (2FA) pour accéder au CRM.');
        }

        return $next($request);
    }
}
