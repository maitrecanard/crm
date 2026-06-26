<?php

namespace App\Http\Middleware;

use App\Models\Prospect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentifie un appel de l'API d'assistance par le **token client** (150 car.)
 * transmis depuis le site du client, et cloisonne la requête sur ce client.
 *
 * Token accepté dans l'en-tête `X-Support-Token` ou `Authorization: Bearer <token>`.
 *
 * Restriction supplémentaire : seuls les **sites déclarés dans les projets** du
 * client (url_prod / url_preprod) peuvent appeler l'API. Le site appelant est lu
 * dans `X-Support-Site` (appels serveur), à défaut `Origin` puis `Referer`.
 *
 * Exposés à la requête : `support_client` (Prospect) et `support_project` (Project
 * dont l'URL correspond au site appelant).
 */
class ResolveSupportClient
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('crm.support_api.enabled')) {
            abort(404);
        }

        $token = $request->header('X-Support-Token')
            ?: $request->bearerToken();

        // Format attendu : exactement 150 caractères (évite des recherches inutiles).
        if (! $token || strlen($token) !== 150) {
            return response()->json(['message' => 'Token d’assistance manquant ou invalide.'], 401);
        }

        $client = Prospect::where('support_token', $token)
            ->where('est_client', true)
            ->first();

        if (! $client) {
            return response()->json(['message' => 'Token d’assistance inconnu.'], 401);
        }

        // Le site appelant doit correspondre à l'URL d'un projet du client.
        $host = self::host(
            $request->header('X-Support-Site')
            ?: $request->header('Origin')
            ?: $request->header('Referer')
        );

        if (! $host) {
            return response()->json(['message' => 'Site appelant non identifié (en-tête Origin manquant).'], 403);
        }

        $project = $client->projects()->get()->first(fn ($p) => in_array(
            $host, [self::host($p->url_prod), self::host($p->url_preprod)], true
        ));

        if (! $project) {
            return response()->json(['message' => 'Ce site n’est pas autorisé : aucun projet du client ne correspond.'], 403);
        }

        $request->attributes->set('support_client', $client);
        $request->attributes->set('support_project', $project);

        return $next($request);
    }

    /** Hôte normalisé d'une URL (minuscule, sans « www. »), ou null. */
    private static function host(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (! str_contains($url, '://')) {
            $url = 'https://'.$url;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return null;
        }
        $host = strtolower($host);

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
