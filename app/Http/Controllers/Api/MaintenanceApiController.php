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
}
