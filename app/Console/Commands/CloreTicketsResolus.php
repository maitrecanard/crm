<?php

namespace App\Console\Commands;

use App\Models\Bug;
use Illuminate\Console\Command;

/**
 * Clôture automatiquement les tickets résolus (statut « livré ») depuis N jours
 * (7 par défaut). La clôture se fait en base directement : AUCUN e-mail n'est
 * envoyé au client (contrairement à un changement de statut via l'interface).
 * Planifié (routes/console.php) : chaque jour à 6h.
 * Usage : php artisan crm:cloture-tickets [--jours=7] [--dry-run]
 */
class CloreTicketsResolus extends Command
{
    protected $signature = 'crm:cloture-tickets {--jours=7 : Nombre de jours après résolution} {--dry-run : N\'écrit rien, affiche le résumé}';

    protected $description = 'Clôture les tickets résolus depuis N jours (sans notifier le client).';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('jours'));
        $seuil = now()->subDays($jours);

        $tickets = Bug::where('statut', 'livre')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $seuil)
            ->get();

        if ($tickets->isEmpty()) {
            $this->info("Aucun ticket à clôturer (résolu depuis plus de {$jours} j).");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY] {$tickets->count()} ticket(s) seraient clôturé(s).");
            foreach ($tickets as $t) {
                $this->line('• '.$t->reference.' — '.$t->titre.' (résolu le '.$t->resolved_at?->format('d/m/Y').')');
            }

            return self::SUCCESS;
        }

        foreach ($tickets as $t) {
            // Mise à jour directe : pas de passage par le contrôleur => aucun e-mail client.
            $t->update(['statut' => 'ferme']);
            $t->logStatut('livre', 'ferme', 'auto');
        }

        $this->info("{$tickets->count()} ticket(s) clôturé(s) automatiquement (sans notification client).");

        return self::SUCCESS;
    }
}
