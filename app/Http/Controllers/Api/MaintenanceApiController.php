<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

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

    /**
     * Envoie un e-mail de test (diagnostic SMTP). Vide d'abord le cache de config
     * pour prendre en compte le .env à jour, puis renvoie le mailer/expéditeur
     * et l'erreur exacte en cas d'échec.
     */
    public function testMail(Request $request)
    {
        abort_unless(config('crm.allow_remote_migrate'), 403, 'Administration distante désactivée.');

        Artisan::call('config:clear');   // force la relecture du .env (MAIL_*)

        $to = $request->input('to') ?: config('crm.support.copy') ?: config('crm.support.email');
        $info = [
            'mailer' => config('mail.default'),
            'host'   => config('mail.mailers.smtp.host'),
            'port'   => config('mail.mailers.smtp.port'),
            'from'   => config('crm.support.email'),
            'to'     => $to,
        ];

        try {
            Mail::raw('Test e-mail du CRM TechCare. Si vous recevez ce message, l\'envoi fonctionne.',
                function ($m) use ($to) {
                    $m->to($to)
                      ->from(config('crm.support.email'), config('crm.support.name'))
                      ->subject('[Support] Test e-mail CRM');
                });

            return response()->json($info + ['ok' => true, 'message' => 'E-mail de test envoyé.']);
        } catch (\Throwable $e) {
            return response()->json($info + ['ok' => false, 'error' => $e->getMessage()], 500);
        }
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
