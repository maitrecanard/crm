<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\Prospect;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function store(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(Interaction::TYPES))],
            'note' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);
        $data['date'] = $data['date'] ?? now();

        $prospect->interactions()->create($data);

        // Avance le pipeline si on est encore au tout début.
        if ($prospect->statut === 'a_contacter' && in_array($data['type'], ['appel', 'email', 'linkedin'])) {
            $prospect->update(['statut' => 'contacte']);
        } elseif ($data['type'] === 'rdv') {
            $prospect->update(['statut' => 'rdv']);
        }

        return back()->with('success', 'Interaction enregistrée.');
    }
}
