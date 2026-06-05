<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Services\TenderUpsert;
use Illuminate\Http\Request;

class TenderApiController extends Controller
{
    public function __construct(private TenderUpsert $upsert) {}

    /** Liste des appels d'offres (app mobile), triés par date limite. */
    public function index(Request $request)
    {
        return Tender::query()
            ->when($request->input('statut'), fn ($q, $v) => $q->where('statut', $v))
            ->orderByRaw('date_limite is null, date_limite asc')
            ->paginate(30);
    }

    public function show(Tender $tender)
    {
        return $tender->load('prospect:id,entreprise,statut');
    }

    /** Import en masse d'appels d'offres : { "tenders": [ {idweb, objet, ...} ] }. */
    public function bulk(Request $request)
    {
        $request->validate([
            'tenders'           => ['required', 'array', 'min:1', 'max:2000'],
            'tenders.*.idweb'   => ['required', 'string'],
        ]);

        $created = $updated = $skipped = 0;
        foreach ($request->input('tenders') as $row) {
            $res = $this->upsert->upsert($row);
            if ($res === null) {
                $skipped++;
            } else {
                $res['created'] ? $created++ : $updated++;
            }
        }

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total'   => Tender::count(),
        ]);
    }

    /**
     * Rattache un dossier de réponse pré-rempli à un appel d'offres (dédup par idweb).
     * Body : { idweb, dossier: {resume, memoire, dpgf, acte, checklist[], generated_at}, montant_ht? }
     * Crée l'AO s'il n'existe pas encore, et le passe en « Dossier en cours » s'il était « À étudier ».
     */
    public function attachDossier(Request $request)
    {
        $data = $request->validate([
            'idweb'      => ['required', 'string'],
            'dossier'    => ['required', 'array'],
            'montant_ht' => ['nullable', 'integer', 'min:0'],
        ]);

        $res = $this->upsert->upsert(['idweb' => $data['idweb'], 'objet' => $request->input('objet', '')]);
        if ($res === null) {
            return response()->json(['message' => 'idweb invalide'], 422);
        }
        $tender = $res['tender'];

        // Préserve l'état coché de la checklist déjà suivie côté CRM.
        $dossier = $data['dossier'];
        $existing = $tender->dossier['checklist'] ?? null;
        if ($existing && empty($dossier['checklist_keep'] ?? null)) {
            $doneByLabel = collect($existing)->pluck('done', 'label');
            $dossier['checklist'] = collect($dossier['checklist'] ?? [])
                ->map(fn ($it) => ['label' => $it['label'], 'done' => (bool) $doneByLabel->get($it['label'], $it['done'] ?? false)])
                ->all();
        }

        $update = ['dossier' => $dossier];
        if (! empty($data['montant_ht'])) {
            $update['montant_ht'] = $data['montant_ht'];
        }
        if ($tender->statut === 'a_etudier') {
            $update['statut'] = 'dossier';   // un dossier est prêt -> on bascule le suivi
        }
        $tender->update($update);

        return response()->json([
            'idweb'      => $tender->idweb,
            'statut'     => $tender->statut,
            'montant_ht' => $tender->montant_ht,
            'message'    => 'Dossier rattaché.',
        ]);
    }
}
