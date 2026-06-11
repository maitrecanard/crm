<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
}
