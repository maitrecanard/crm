<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $prospectStats = Prospect::select('statut', DB::raw('count(*) as n'))
            ->groupBy('statut')->pluck('n', 'statut');
        $totalP = Prospect::count();

        $tenderStats = Tender::select('statut', DB::raw('count(*) as n'))
            ->groupBy('statut')->pluck('n', 'statut');

        $aoOuverts = ['a_etudier', 'go', 'dossier', 'depose'];

        return Inertia::render('Dashboard', [
            'statutsProspect' => Prospect::STATUTS,
            'statutsTender'   => Tender::STATUTS,
            'prospectStats'   => $prospectStats,
            'tenderStats'     => $tenderStats,
            'kpis' => [
                'total'       => $totalP,
                'a_contacter' => $prospectStats['a_contacter'] ?? 0,
                'en_cours'    => $totalP - ($prospectStats['a_contacter'] ?? 0)
                                  - ($prospectStats['perdu'] ?? 0) - ($prospectStats['gagne'] ?? 0),
                'rdv'         => $prospectStats['rdv'] ?? 0,
                'gagne'       => $prospectStats['gagne'] ?? 0,
                'ao_ouverts'  => Tender::whereIn('statut', $aoOuverts)->count(),
            ],
            'aContacterEmail' => Prospect::where('statut', 'a_contacter')
                ->whereNotNull('email')->where('email', '<>', '')
                ->orderBy('entreprise')
                ->limit(20)
                ->get(['id', 'entreprise', 'localite', 'secteur', 'email']),
            'avecEmailTotal' => Prospect::where('statut', 'a_contacter')
                ->whereNotNull('email')->where('email', '<>', '')->count(),
            'relances' => Prospect::whereNotNull('prochaine_relance')
                ->whereNotIn('statut', ['gagne', 'perdu'])
                ->whereDate('prochaine_relance', '<=', now()->addDays(7))
                ->orderBy('prochaine_relance')
                ->limit(12)
                ->get(['id', 'entreprise', 'localite', 'statut', 'prochaine_relance']),
            'aoUrgents' => Tender::whereIn('statut', $aoOuverts)
                ->whereNotNull('date_limite')
                ->whereDate('date_limite', '>=', now())
                ->orderBy('date_limite')
                ->limit(8)
                ->get(),
            // Factures mensuelles en retard : mois sans référence passé l'échéance.
            'facturesEnRetard' => Prospect::alertesFacturation(),
        ]);
    }
}
