<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Prospect;
use App\Models\Tender;
use Illuminate\Support\Str;

/**
 * Promotion d'un prospect « Gagné » : il devient client et sa demande devient
 * un projet (avec des tâches de gestion pré-remplies).
 */
class ClientPromotion
{
    /** Phases par défaut si on n'a pas de DPGF pour pré-remplir le plan. */
    private const PHASES = [
        'Cadrage & lancement',
        'Conception UX / UI',
        'Développement',
        'Recette & corrections',
        'Mise en production',
        'Formation & garantie',
    ];

    /** Idempotent : marque client + crée le projet s'il n'existe pas encore. */
    public static function promote(Prospect $prospect): ?Project
    {
        if (! $prospect->est_client) {
            $prospect->est_client = true;
            $prospect->client_depuis = $prospect->client_depuis ?? now()->toDateString();
            $prospect->saveQuietly();   // pas de ré-entrée dans l'observer
        }

        if ($prospect->projects()->exists()) {
            return null;   // déjà converti
        }

        $tender = $prospect->tenders()
            ->orderByRaw('montant_ht is null, montant_ht desc')
            ->first();

        $demande = self::demande($prospect, $tender);

        $project = Project::create([
            'prospect_id' => $prospect->id,
            'tender_id'   => $tender?->id,
            'titre'       => $demande,
            'description' => $prospect->signal_alerte ?: $prospect->notes,
            'statut'      => 'cadrage',
            'budget'      => $tender?->montant_ht,
            'date_debut'  => now()->toDateString(),
        ]);

        foreach (self::phases($tender) as $i => $titre) {
            $project->tasks()->create(['titre' => $titre, 'ordre' => $i]);
        }

        return $project;
    }

    /** Intitulé du projet à partir de la demande du prospect. */
    private static function demande(Prospect $prospect, ?Tender $tender): string
    {
        $base = $tender?->objet ?: $prospect->signal_alerte ?: '';
        $base = trim(preg_replace('/^Besoin exprimé\s*:\s*«?\s*/iu', '', $base));
        $base = trim(explode('»', $base)[0]);
        $base = Str::limit($base, 90, '');

        return $base !== '' ? Str::ucfirst(mb_strtolower($base)) : "Projet — {$prospect->entreprise}";
    }

    /** Plan de tâches : postes de la DPGF si dispo, sinon phases standard. */
    private static function phases(?Tender $tender): array
    {
        $dpgf = is_array($tender?->dossier ?? null) ? ($tender->dossier['dpgf'] ?? '') : '';
        if (is_string($dpgf) && $dpgf !== '') {
            $postes = [];
            foreach (explode("\n", $dpgf) as $line) {
                if (! str_starts_with(ltrim($line), '|')) {
                    continue;
                }
                $cells = array_map('trim', explode('|', trim(trim($line), '|')));
                // Lignes « | n | Poste | Jours | ... » : on garde la 2e colonne.
                if (count($cells) >= 3 && ctype_digit($cells[0]) && stripos($cells[1], 'total') === false) {
                    $postes[] = $cells[1];
                }
            }
            if (count($postes) >= 3) {
                return $postes;
            }
        }

        return self::PHASES;
    }
}
