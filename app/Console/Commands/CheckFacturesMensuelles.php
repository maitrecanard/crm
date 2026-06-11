<?php

namespace App\Console\Commands;

use App\Models\FactureMensuelle;
use App\Models\Prospect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Surveillance de facturation mensuelle : pour chaque client surveillé, repère
 * les mois échus sans référence de facture saisie (= facture non envoyée) et
 * envoie une alerte e-mail. Chaque mois en retard n'est alerté qu'une fois
 * (champ `alerte_envoyee_le`). À planifier en cron : php artisan crm:factures-alertes
 */
class CheckFacturesMensuelles extends Command
{
    protected $signature = 'crm:factures-alertes {--dry-run : Liste sans envoyer ni marquer}';

    protected $description = 'Alerte sur les factures mensuelles non envoyées (référence manquante).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $nouvelles = [];   // retards pas encore alertés

        $clients = Prospect::where('facturation_active', true)
            ->whereNotNull('facturation_debut')
            ->with('facturesMensuelles')
            ->get();

        foreach ($clients as $client) {
            foreach ($client->facturesEnRetard() as $mois) {
                // Matérialise la ligne du mois en retard pour porter l'anti-spam.
                $facture = $client->facturesMensuelles()->firstOrNew(
                    ['periode' => $mois['periode'].'-01']
                );
                if ($facture->alerte_envoyee_le) {
                    continue; // déjà alerté
                }
                $nouvelles[] = [
                    'entreprise' => $client->entreprise,
                    'mois'       => $mois['mois_label'],
                    'echeance'   => $mois['echeance'],
                    'libelle'    => $client->facturation_libelle,
                ];
                if (! $dry) {
                    $facture->alerte_envoyee_le = now();
                    $facture->save();
                }
            }
        }

        if (! $nouvelles) {
            $this->info('Aucune nouvelle facture mensuelle en retard.');
            return self::SUCCESS;
        }

        $lignes = array_map(
            fn ($r) => "• {$r['entreprise']}"
                .($r['libelle'] ? " ({$r['libelle']})" : '')
                ." — facture {$r['mois']} NON envoyée (échéance dépassée le {$r['echeance']})",
            $nouvelles
        );
        $corps = "Factures mensuelles non envoyées (référence manquante) :\n\n"
            .implode("\n", $lignes)
            ."\n\nSaisis la référence de facture sur la fiche du client pour lever l'alerte.\n";

        $this->info(count($nouvelles).' facture(s) en retard.');
        $this->line($corps);

        if ($dry) {
            return self::SUCCESS;
        }

        $dest = config('crm.vendeur.email') ?: config('crm.support.copy');
        if ($dest) {
            try {
                Mail::raw($corps, fn ($m) => $m->to($dest)
                    ->subject('[CRM] '.count($nouvelles).' facture(s) mensuelle(s) non envoyée(s)')
                    ->from(config('crm.support.email'), config('crm.support.name')));
            } catch (\Throwable $e) {
                $this->warn('Alerte non envoyée par e-mail : '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
