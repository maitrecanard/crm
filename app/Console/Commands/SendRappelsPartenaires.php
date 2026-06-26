<?php

namespace App\Console\Commands;

use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\RappelTachesPartenaire;
use Illuminate\Console\Command;

/**
 * Rappelle aux admins les tâches transmises par les partenaires et non terminées.
 * Planifié (routes/console.php) : 7h en semaine, 10h le week-end.
 * Usage : php artisan crm:rappels-partenaires [--dry-run]
 */
class SendRappelsPartenaires extends Command
{
    protected $signature = 'crm:rappels-partenaires {--dry-run : N\'envoie rien, affiche le résumé}';

    protected $description = 'Rappel quotidien des tâches partenaire à réaliser (e-mail + in-app).';

    public function handle(): int
    {
        $taches = ProjectTask::enAttentePartenaire()
            ->with('partenaire:id,nom', 'project:id,titre')
            ->orderBy('echeance')
            ->get();

        if ($taches->isEmpty()) {
            $this->info('Aucune tâche partenaire en attente : pas de rappel.');

            return self::SUCCESS;
        }

        $admins = User::where('role', '!=', 'partenaire')->get();

        if ($admins->isEmpty()) {
            $this->warn('Aucun administrateur à notifier.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY] {$taches->count()} tâche(s) en attente → {$admins->count()} admin(s).");
            foreach ($taches as $t) {
                $this->line('• '.$t->titre.($t->echeance ? ' (éch. '.$t->echeance->format('d/m/Y').')' : ''));
            }

            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            $admin->notify(new RappelTachesPartenaire($taches));
        }

        $this->info("Rappel envoyé : {$taches->count()} tâche(s) → {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
