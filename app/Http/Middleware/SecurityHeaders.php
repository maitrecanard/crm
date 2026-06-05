<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité HTTP : limite clickjacking, sniffing MIME, fuite de
 * referrer, et force HTTPS (HSTS) côté navigateur.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        // HSTS uniquement en HTTPS (sinon on bloquerait le dev en http).
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // On retire l'empreinte serveur si présente.
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
