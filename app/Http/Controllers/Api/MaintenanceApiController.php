<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

/**
 * Maintenance déclenchable à distance par le SDK (token Sanctum requis).
 * Permet d'appliquer les migrations en attente sans accès SSH au serveur.
 */
class MaintenanceApiController extends Controller
{
    /** Applique les migrations en attente puis vide les caches. */
    public function migrate()
    {
        abort_unless(config('crm.allow_remote_migrate'), 403, 'Migration distante désactivée.');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrate = trim(Artisan::output());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        // Vidage des caches en best-effort : ne doit jamais faire échouer la migration.
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $cmd) {
            try {
                Artisan::call($cmd);
            } catch (\Throwable $e) {
                // cache absent / non inscriptible : on ignore.
            }
        }

        return response()->json([
            'ok'     => true,
            'output' => $migrate ?: 'Rien à migrer (déjà à jour).',
        ]);
    }

    /** État des migrations (sans rien exécuter). */
    public function status()
    {
        Artisan::call('migrate:status');

        return response()->json(['output' => trim(Artisan::output())]);
    }

    /**
     * Régénère le token API : crée un nouveau token et révoque TOUS les autres
     * (dont celui, fuité, présent dans l'historique git). Authentifié avec
     * l'ancien token ; renvoie le nouveau (à reporter dans .crm.env).
     */
    public function rotateToken(\Illuminate\Http\Request $request)
    {
        abort_unless(config('crm.allow_remote_migrate'), 403, 'Administration distante désactivée.');

        $user = $request->user();
        $new = $user->createToken('crm-sdk');
        $newId = $new->accessToken->getKey();

        // Révoque tous les autres tokens (ancien token SDK fuité inclus).
        $user->tokens()->where('id', '!=', $newId)->delete();

        return response()->json([
            'token'   => $new->plainTextToken,
            'message' => 'Token régénéré. Anciens tokens révoqués.',
        ]);
    }
}
