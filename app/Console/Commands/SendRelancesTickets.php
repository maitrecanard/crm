<?php

namespace App\Console\Commands;

use App\Models\Bug;
use App\Models\User;
use App\Notifications\RelanceTickets;
use Illuminate\Console\Command;

/**
 * Relance les admins sur les tickets en cours, matin et soir.
 * Planifié (routes/console.php) : 8h et 18h.
 * Usage : php artisan crm:relances-tickets [--moment=matin|soir] [--dry-run]
 */
class SendRelancesTickets extends Command
{
    protected $signature = 'crm:relances-tickets {--moment= : matin ou soir (déduit de l\'heure si omis)} {--dry-run : N\'envoie rien, affiche le résumé}';

    protected $description = 'Relance quotidienne (matin/soir) des tickets en cours.';

    public function handle(): int
    {
        $rang = ['bloquant' => 0, 'majeur' => 1, 'mineur' => 2];

        $tickets = Bug::whereIn('statut', Bug::OUVERTS)
            ->with('project.prospect:id,entreprise')
            ->get()
            // Les plus graves d'abord, puis les plus anciens.
            ->sortBy(fn (Bug $b) => [$rang[$b->gravite] ?? 9, $b->created_at?->timestamp ?? 0])
            ->values();

        $moment = in_array($this->option('moment'), ['matin', 'soir'], true)
            ? $this->option('moment')
            : (now()->hour < 14 ? 'matin' : 'soir');

        if ($tickets->isEmpty()) {
            $this->info('Aucun ticket en cours : pas de relance.');

            return self::SUCCESS;
        }

        $admins = User::where('role', '!=', 'partenaire')->get();

        if ($admins->isEmpty()) {
            $this->warn('Aucun administrateur à notifier.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY] {$tickets->count()} ticket(s) en cours → {$admins->count()} admin(s) [{$moment}].");
            foreach ($tickets as $t) {
                $this->line('• '.$t->reference.' — '.$t->titre.' ('.$t->gravite.')');
            }

            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            $admin->notify(new RelanceTickets($tickets, $moment));
        }

        $this->info("Relance {$moment} envoyée : {$tickets->count()} ticket(s) → {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
