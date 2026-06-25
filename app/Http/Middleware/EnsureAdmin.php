<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve l'accès aux comptes admin. Un partenaire connecté est renvoyé vers
 * son portail au lieu d'atteindre le back-office.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isPartenaire()) {
            return redirect()->route('portail.index');
        }

        return $next($request);
    }
}
