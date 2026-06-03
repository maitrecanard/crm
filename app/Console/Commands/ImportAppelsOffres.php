<?php

namespace App\Console\Commands;

use App\Models\Prospect;
use App\Models\Tender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportAppelsOffres extends Command
{
    protected $signature = 'crm:import-ao';

    protected $description = 'Importe les appels d\'offres MAPA web/logiciel encore ouverts depuis BOAMP.';

    private const API = 'https://boamp-datadila.opendatasoft.com/api/explore/v2.1/catalog/datasets/boamp/records';

    private const KEYWORDS = [
        'site internet', 'site web', 'application mobile', 'application web',
        'plateforme numérique', 'refonte du site', 'développement logiciel',
        'portail web', 'solution logicielle',
    ];

    private const DEPARTEMENTS = [
        '01' => 'Ain', '06' => 'Alpes-Maritimes', '13' => 'Bouches-du-Rhône',
        '29' => 'Finistère', '31' => 'Haute-Garonne', '33' => 'Gironde',
        '34' => 'Hérault', '35' => 'Ille-et-Vilaine', '44' => 'Loire-Atlantique',
        '45' => 'Loiret', '49' => 'Maine-et-Loire', '59' => 'Nord', '67' => 'Bas-Rhin',
        '69' => 'Rhône', '75' => 'Paris', '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne',
        '78' => 'Yvelines', '83' => 'Var', '84' => 'Vaucluse', '86' => 'Vienne',
        '91' => 'Essonne', '92' => 'Hauts-de-Seine', '93' => 'Seine-Saint-Denis',
        '94' => 'Val-de-Marne', '95' => "Val-d'Oise",
    ];

    public function handle(): int
    {
        $today = now()->toDateString();
        $intents = collect(self::KEYWORDS)->map(fn ($k) => "objet like \"{$k}\"")->implode(' or ');
        $where = "({$intents}) and nature_categorise_libelle like \"Avis de marché\" "
               . "and type_procedure = \"PROCEDURE_ADAPTE\" "
               . "and datelimitereponse > date'{$today}'";

        $created = $updated = 0;
        $openIds = [];

        for ($offset = 0; $offset < 400; $offset += 100) {
            $resp = Http::timeout(30)->get(self::API, [
                'where'    => $where,
                'order_by' => 'datelimitereponse asc',
                'limit'    => 100,
                'offset'   => $offset,
            ]);
            if ($resp->failed()) {
                $this->error('Échec API BOAMP: HTTP '.$resp->status());
                break;
            }
            $results = $resp->json('results', []);
            foreach ($results as $rec) {
                $idweb = $rec['idweb'] ?? null;
                if (! $idweb) {
                    continue;
                }
                $openIds[] = $idweb;

                $tender = Tender::firstOrNew(['idweb' => $idweb]);
                $isNew = ! $tender->exists;
                $cd = $rec['code_departement'] ?? null;
                $code = is_array($cd) ? ($cd[0] ?? null) : $cd;
                $acheteur = $rec['nomacheteur'] ?? null;
                $departement = self::DEPARTEMENTS[$code] ?? $code;
                $url = "https://www.boamp.fr/pages/avis/?q=idweb:{$idweb}";
                $objet = $rec['objet'] ?? '';

                $tender->fill([
                    'objet'         => $objet,
                    'acheteur'      => $acheteur,
                    'departement'   => $departement,
                    'procedure'     => 'Procédure adaptée (MAPA)',
                    'date_parution' => $rec['dateparution'] ?? null,
                    'date_limite'   => $rec['datelimitereponse'] ?? null,
                    'url'           => $url,
                    'prospect_id'   => $this->linkProspect($idweb, $url, $acheteur, $departement, $objet),
                ]);
                if ($isNew) {
                    $tender->statut = 'a_etudier';
                }
                $tender->save();
                $isNew ? $created++ : $updated++;
            }
            if (count($results) < 100) {
                break;
            }
        }

        // Marque "expirés" les AO en base qui ne sont plus ouverts (sauf déjà clôturés).
        $closed = Tender::whereNotIn('idweb', $openIds)
            ->whereNotIn('statut', ['gagne', 'perdu', 'abandonne', 'expire'])
            ->update(['statut' => 'expire']);

        // Backfill : relie les AO restants (ex. déjà expirés) à leur acheteur.
        Tender::whereNull('prospect_id')->get()->each(function (Tender $t) {
            $t->update(['prospect_id' => $this->linkProspect(
                $t->idweb, $t->url, $t->acheteur, $t->departement, $t->objet ?? '')]);
        });

        $linked = Tender::whereNotNull('prospect_id')->count();
        $this->info("AO ouverts : {$created} créés, {$updated} mis à jour. {$closed} marqués expirés.");
        $this->info("Total en base : ".Tender::count()." ({$linked} liés à un prospect).");

        return self::SUCCESS;
    }

    /**
     * Relie l'AO à son prospect acheteur (le crée s'il n'existe pas encore).
     * La clé est l'URL BOAMP de l'avis, commune à l'import des prospects "besoins".
     */
    private function linkProspect(string $idweb, string $url, ?string $acheteur, ?string $departement, string $objet): int
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
