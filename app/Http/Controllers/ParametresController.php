<?php

namespace App\Http\Controllers;

use App\Models\Contrat;
use App\Models\Prospect;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParametresController extends Controller
{
    public function edit()
    {
        return Inertia::render('Parametres/Index', [
            'contratConditions' => Setting::get('contrat_conditions', config('crm.contrat.conditions_defaut')),
            'conditionsDefaut'  => config('crm.contrat.conditions_defaut'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'contrat_conditions' => ['required', 'string'],
        ]);
        Setting::put('contrat_conditions', $data['contrat_conditions']);

        return back()->with('success', 'Modèle de conditions enregistré.');
    }

    /** Génère un PDF de contrat d'exemple (client fictif) pour tester la chaîne dompdf. */
    public function testPdf()
    {
        $contrat = new Contrat([
            'reference'    => 'CONTRAT-TEST',
            'objet'        => 'Exemple — développement d’une application web (test de génération PDF)',
            'montant_ht'   => 9500,
            'conditions'   => Setting::get('contrat_conditions', config('crm.contrat.conditions_defaut')),
            'date_contrat' => now()->toDateString(),
        ]);
        $client = new Prospect([
            'entreprise' => 'Client de démonstration',
            'localite'   => 'Poitiers (86)',
            'email'      => 'client@exemple.fr',
            'telephone'  => '05 49 00 00 00',
        ]);

        return Pdf::loadView('contrats.pdf', [
            'contrat' => $contrat,
            'client'  => $client,
            'vendeur' => config('crm.vendeur'),
        ])->setPaper('a4')->download('contrat-test.pdf');
    }
}
