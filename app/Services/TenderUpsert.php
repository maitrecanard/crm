<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\Tender;

/**
 * Upsert d'un appel d'offres (dédup par idweb) + liaison automatique au
 * prospect acheteur (créé si absent). N'écrase pas le suivi (statut, notes).
 */
class TenderUpsert
{
    /**
     * @return array{tender: Tender, created: bool}|null
     */
    public function upsert(array $row): ?array
    {
        $idweb = trim((string) ($row['idweb'] ?? ''));
        if ($idweb === '') {
            return null;
        }

        $url = $row['url'] ?? "https://www.boamp.fr/pages/avis/?q=idweb:{$idweb}";
        $acheteur = $row['acheteur'] ?? null;
        $departement = $row['departement'] ?? null;
        $objet = (string) ($row['objet'] ?? '');

        $tender = Tender::firstOrNew(['idweb' => $idweb]);
        $created = ! $tender->exists;

        $tender->fill([
            'objet'         => $objet,
            'acheteur'      => $acheteur,
            'departement'   => $departement,
            'procedure'     => $row['procedure'] ?? 'Procédure adaptée (MAPA)',
            'date_parution' => $row['date_parution'] ?? null,
            'date_limite'   => $row['date_limite'] ?? null,
            'url'           => $url,
            'prospect_id'   => $this->linkProspect($idweb, $url, $acheteur, $departement, $objet),
        ]);
        if ($created) {
            $tender->statut = 'a_etudier';
        }
        $tender->save();

        return ['tender' => $tender, 'created' => $created];
    }

    public function linkProspect(string $idweb, string $url, ?string $acheteur, ?string $departement, string $objet): int
    {
        $prospect = Prospect::firstOrNew(['cle' => $url]);
        if (! $prospect->exists) {
            $prospect->fill([
                'entreprise'     => $acheteur ?: "Acheteur {$idweb}",
                'localite'       => $departement,
                'categorie'      => "Besoin logiciel exprimé (appel d'offres public)",
                'secteur'        => 'secteur public / collectivité',
                'signal_alerte'  => $objet,
                'source_url'     => $url,
                'requete'        => "boamp:{$idweb}",
                'source_fichier' => 'besoins',
                'statut'         => 'a_contacter',
            ]);
            $prospect->save();
        }

        return $prospect->id;
    }
}
