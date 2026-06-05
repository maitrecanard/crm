<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'statut', 'source_fichier', 'secteur', 'localite']);

        $query = Prospect::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) =>
                $w->where('entreprise', 'like', "%{$v}%")
                  ->orWhere('localite', 'like', "%{$v}%")
                  ->orWhere('secteur', 'like', "%{$v}%")
                  ->orWhere('signal_alerte', 'like', "%{$v}%")))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['source_fichier'] ?? null, fn ($q, $v) => $q->where('source_fichier', $v))
            ->when($filters['secteur'] ?? null, fn ($q, $v) => $q->where('secteur', $v))
            ->when($filters['localite'] ?? null, fn ($q, $v) => $q->where('localite', $v));

        $prospects = $query->orderByRaw("CASE statut
                WHEN 'rdv' THEN 0 WHEN 'relance' THEN 1 WHEN 'contacte' THEN 2
                WHEN 'a_contacter' THEN 3 WHEN 'gagne' THEN 4 ELSE 5 END")
            ->orderBy('entreprise')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Prospects/Index', [
            'prospects' => $prospects,
            'filters'   => $filters,
            'statuts'   => Prospect::STATUTS,
            'stats'     => Prospect::select('statut', DB::raw('count(*) as n'))
                            ->groupBy('statut')->pluck('n', 'statut'),
            'sources'   => Prospect::select('source_fichier')->distinct()
                            ->orderBy('source_fichier')->pluck('source_fichier'),
            'secteurs'  => Prospect::select('secteur')->whereNotNull('secteur')
                            ->distinct()->orderBy('secteur')->pluck('secteur'),
            'total'     => Prospect::count(),
        ]);
    }

    public function show(Prospect $prospect)
    {
        $prospect->load('interactions', 'tenders:id,prospect_id,acheteur,objet,date_limite,statut');

        return Inertia::render('Prospects/Show', [
            'prospect'    => $prospect,
            'statuts'     => Prospect::STATUTS,
            'typeOptions' => \App\Models\Interaction::TYPES,
        ]);
    }

    public function update(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'statut'            => ['required', 'in:'.implode(',', array_keys(Prospect::STATUTS))],
            'notes'             => ['nullable', 'string'],
            'prochaine_relance' => ['nullable', 'date'],
        ]);

        $prospect->update($data);

        return back()->with('success', 'Prospect mis à jour.');
    }

    /** Enregistre (ou réinitialise) un scénario personnalisé pour ce prospect. */
    public function saveScenario(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'key'   => ['required', 'in:appel,email,linkedin'],
            'value' => ['nullable', 'string', 'max:5000'],
        ]);

        $scenarios = $prospect->scenarios ?? [];
        if (blank($data['value'])) {
            unset($scenarios[$data['key']]);   // réinitialisation -> retour au modèle généré
        } else {
            $scenarios[$data['key']] = $data['value'];
        }
        $prospect->update(['scenarios' => $scenarios ?: null]);

        return back(fallback: route('prospects.show', $prospect));
    }
}
