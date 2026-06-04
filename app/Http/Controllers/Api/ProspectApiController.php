<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Services\ProspectUpsert;
use Illuminate\Http\Request;

class ProspectApiController extends Controller
{
    public function __construct(private ProspectUpsert $upsert) {}

    /** Liste paginée (app mobile) avec filtres q/statut/source/secteur. */
    public function index(Request $request)
    {
        return Prospect::query()
            ->when($request->input('q'), fn ($query, $v) => $query->where(fn ($w) =>
                $w->where('entreprise', 'like', "%{$v}%")
                  ->orWhere('localite', 'like', "%{$v}%")
                  ->orWhere('secteur', 'like', "%{$v}%")))
            ->when($request->input('statut'), fn ($query, $v) => $query->where('statut', $v))
            ->when($request->input('source_fichier'), fn ($query, $v) => $query->where('source_fichier', $v))
            ->orderBy('entreprise')
            ->paginate(30);
    }

    /** Fiche d'un prospect avec son historique. */
    public function show(Prospect $prospect)
    {
        return $prospect->load('interactions', 'tenders:id,prospect_id,objet,date_limite,statut');
    }

    /** Met à jour le suivi (statut, notes, relance) depuis le mobile. */
    public function updateSuivi(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'statut'            => ['sometimes', 'in:'.implode(',', array_keys(Prospect::STATUTS))],
            'notes'             => ['nullable', 'string'],
            'prochaine_relance' => ['nullable', 'date'],
        ]);
        $prospect->update($data);

        return $prospect;
    }

    /** Ajoute une interaction (appel, email, note…). */
    public function addInteraction(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(\App\Models\Interaction::TYPES))],
            'note' => ['nullable', 'string'],
        ]);
        $data['date'] = now();
        $prospect->interactions()->create($data);

        return response()->json(['ok' => true], 201);
    }

    /** Statistiques pipeline (pour l'écran d'accueil mobile). */
    public function stats()
    {
        return response()->json([
            'total'    => Prospect::count(),
            'pipeline' => Prospect::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut'),
            'statuts'  => Prospect::STATUTS,
        ]);
    }

    /** Crée/met à jour un prospect unique. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'entreprise'    => ['required', 'string', 'max:255'],
            'localite'      => ['nullable', 'string', 'max:255'],
            'telephone'     => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'string', 'max:255'],
            'categorie'     => ['nullable', 'string', 'max:255'],
            'secteur'       => ['nullable', 'string', 'max:255'],
            'signal_alerte' => ['nullable', 'string'],
            'source_url'    => ['nullable', 'string', 'max:1000'],
            'requete'       => ['nullable', 'string', 'max:255'],
            'source_fichier' => ['nullable', 'string', 'max:100'],
        ]);

        $res = $this->upsert->upsert($data);

        return response()->json([
            'id'      => $res['prospect']->id,
            'created' => $res['created'],
        ], $res['created'] ? 201 : 200);
    }

    /** Import en masse : { "prospects": [ {...}, ... ] }. */
    public function bulk(Request $request)
    {
        $request->validate([
            'prospects' => ['required', 'array', 'min:1', 'max:2000'],
            'source'    => ['nullable', 'string', 'max:100'],
        ]);
        // Les lignes invalides (entreprise vide) sont ignorées, pas rejetées.

        $created = $updated = $skipped = 0;
        foreach ($request->input('prospects') as $row) {
            $res = $this->upsert->upsert($row, $request->input('source'));
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
            'total'   => Prospect::count(),
        ]);
    }
}
