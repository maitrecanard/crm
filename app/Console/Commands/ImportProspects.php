<?php

namespace App\Console\Commands;

use App\Models\Prospect;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportProspects extends Command
{
    protected $signature = 'crm:import {--path= : Dossier des fichiers result_*.json}';

    protected $description = 'Importe les prospects depuis les result_*.json (dédup, sans écraser le suivi CRM).';

    /** Fichier source => libellé interne. */
    private const FILES = [
        'result.json'                   => 'pme',
        'result_clients_tech.json'      => 'clients_tech',
        'result_grands_comptes.json'    => 'grands_comptes',
        'result_besoins_logiciels.json' => 'besoins',
    ];

    public function handle(): int
    {
        $dir = $this->option('path') ?: realpath(base_path('..'));
        $this->info("Import depuis : {$dir}");

        $created = $updated = $skipped = 0;

        foreach (self::FILES as $file => $source) {
            $full = rtrim((string) $dir, '/')."/{$file}";
            if (! is_file($full)) {
                $this->warn("  ⨯ {$file} introuvable");
                continue;
            }
            $rows = json_decode(file_get_contents($full), true);
            if (! is_array($rows)) {
                $this->warn("  ⨯ {$file} illisible");
                continue;
            }

            foreach ($rows as $row) {
                $entreprise = trim($row['entreprise'] ?? '');
                if ($entreprise === '') {
                    $skipped++;
                    continue;
                }
                $cle = $this->cle($row, $entreprise, $source);

                $prospect = Prospect::firstOrNew(['cle' => $cle]);
                $isNew = ! $prospect->exists;

                // Champs « données » : toujours rafraîchis depuis la source.
                $prospect->fill([
                    'entreprise'     => $entreprise,
                    'localite'       => $row['localite'] ?? null,
                    'telephone'      => ($row['telephone'] ?? '') ?: null,
                    'email'          => ($row['email'] ?? '') ?: null,
                    'categorie'      => $row['categorie'] ?? null,
                    'secteur'        => $row['secteur'] ?? null,
                    'signal_alerte'  => $row['signal_alerte'] ?? null,
                    'source_url'     => $row['source_url'] ?? null,
                    'requete'        => $row['requete'] ?? null,
                    'source_fichier' => $source,
                ]);

                // Champs CRM : posés à la création, JAMAIS écrasés ensuite.
                if ($isNew) {
                    $prospect->statut = 'a_contacter';
                }

                $prospect->save();
                $isNew ? $created++ : $updated++;
            }
            $this->line("  ✓ {$file} ({$source})");
        }

        $this->newLine();
        $this->info("Terminé : {$created} créés, {$updated} mis à jour, {$skipped} ignorés.");
        $this->info('Total en base : '.Prospect::count().' prospects.');

        return self::SUCCESS;
    }

    private function cle(array $row, string $entreprise, string $source): string
    {
        $url = trim($row['source_url'] ?? '');
        if ($url !== '' && ! Str::contains($url, 'openstreetmap.org/')) {
            return $url;            // URL unique par entité (annuaire / BOAMP / site)
        }
        return Str::lower($source.'|'.$entreprise.'|'.($row['localite'] ?? ''));
    }
}
