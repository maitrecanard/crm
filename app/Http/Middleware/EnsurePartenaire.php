<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Réserve le portail aux comptes partenaire rattachés à un partenaire actif. */
class EnsurePartenaire
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isPartenaire() || ! $user->partenaire_id) {
            abort(403);
        }

        return $next($request);
    }
}
