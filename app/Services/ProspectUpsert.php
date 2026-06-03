<?php

namespace App\Services;

use App\Models\Prospect;
use Illuminate\Support\Str;

/**
 * Upsert d'un prospect avec déduplication, sans écraser le suivi CRM
 * (statut, notes, prochaine_relance). Partagé par la commande d'import locale
 * et par l'API.
 */
class ProspectUpsert
{
    /**
     * @param  array  $row  Données du prospect (entreprise, localite, telephone, …)
     * @return array{prospect: Prospect, created: bool}|null
     */
    public function upsert(array $row, ?string $source = null): ?array
    {
        $entreprise = trim((string) ($row['entreprise'] ?? ''));
        if ($entreprise === '') {
            return null;
        }
        $source = $source ?: ($row['source_fichier'] ?? null);
        $cle = $this->cle($row, $entreprise, (string) $source);

        $prospect = Prospect::firstOrNew(['cle' => $cle]);
        $created = ! $prospect->exists;

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

        if ($created) {
            $prospect->statut = 'a_contacter';   // champ CRM posé une seule fois
        }

        $prospect->save();

        return ['prospect' => $prospect, 'created' => $created];
    }

    public function cle(array $row, string $entreprise, string $source): string
    {
        $url = trim((string) ($row['source_url'] ?? ''));
        if ($url !== '' && ! Str::contains($url, 'openstreetmap.org/')) {
            return $url;
        }

        return Str::lower($source.'|'.$entreprise.'|'.($row['localite'] ?? ''));
    }
}
