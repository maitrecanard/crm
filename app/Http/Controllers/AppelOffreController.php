<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Services\DossierDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AppelOffreController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'statut']);

        $tenders = Tender::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) =>
                $w->where('objet', 'like', "%{$v}%")
                  ->orWhere('acheteur', 'like', "%{$v}%")
                  ->orWhere('departement', 'like', "%{$v}%")))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->orderByRaw('date_limite is null, date_limite asc')
            ->get();

        return Inertia::render('AppelsOffres/Index', [
            'tenders' => $tenders,
            'filters' => $filters,
            'statuts' => Tender::STATUTS,
            'stats'   => Tender::select('statut', DB::raw('count(*) as n'))
                            ->groupBy('statut')->pluck('n', 'statut'),
            'total'   => Tender::count(),
        ]);
    }

    public function show(Tender $tender)
    {
        $tender->load('prospect:id,entreprise,statut,telephone,email');

        return Inertia::render('AppelsOffres/Show', [
            'tender'  => $tender,
            'statuts' => Tender::STATUTS,
        ]);
    }

    public function update(Request $request, Tender $tender)
    {
        $data = $request->validate([
            'statut' => ['required', 'in:'.implode(',', array_keys(Tender::STATUTS))],
            'notes'  => ['nullable', 'string'],
        ]);
        $tender->update($data);

        return back()->with('success', 'Appel d\'offres mis à jour.');
    }

    public function refresh()
    {
        Artisan::call('crm:import-ao');

        return back()->with('success', 'Appels d\'offres rafraîchis depuis BOAMP.');
    }

    /** Télécharge le dossier de réponse en .doc (ouvrable dans Word). */
    public function downloadDoc(Tender $tender)
    {
        abort_unless(is_array($tender->dossier) && $tender->dossier, 404, 'Aucun dossier rattaché à cet appel d\'offres.');

        $idweb = $tender->idweb ?: (string) $tender->id;
        $html  = DossierDoc::html($tender->dossier, $idweb);

        return response($html, 200, [
            'Content-Type'        => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="dossier-'.$idweb.'.doc"',
        ]);
    }

    /** Met à jour l'état coché de la checklist de dépôt du dossier. */
    public function saveChecklist(Request $request, Tender $tender)
    {
        $data = $request->validate([
            'checklist'              => ['present', 'array'],
            'checklist.*.label'      => ['required', 'string', 'max:300'],
            'checklist.*.done'       => ['required', 'boolean'],
        ]);

        $dossier = $tender->dossier ?? [];
        $dossier['checklist'] = $data['checklist'];
        $tender->update(['dossier' => $dossier]);

        return back(fallback: route('ao.show', $tender));
    }
}
