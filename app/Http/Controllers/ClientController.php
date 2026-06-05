<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Prospect::query()
            ->where('est_client', true)
            ->with(['projects:id,prospect_id,titre,statut'])
            ->withCount('projects')
            ->when($request->input('q'), fn ($q, $v) => $q->where('entreprise', 'like', "%{$v}%"))
            ->orderByDesc('client_depuis')
            ->orderBy('entreprise')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only('q'),
            'total'   => Prospect::where('est_client', true)->count(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    /** Création manuelle d'un client existant (hors prospection). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'entreprise' => ['required', 'string', 'max:255'],
            'localite'   => ['nullable', 'string', 'max:255'],
            'telephone'  => ['nullable', 'string', 'max:60'],
            'email'      => ['nullable', 'email', 'max:255'],
            'secteur'    => ['nullable', 'string', 'max:255'],
            'notes'      => ['nullable', 'string'],
        ]);

        $prospect = new Prospect($data);
        $prospect->forceFill([
            'cle'            => 'client-manuel-'.Str::uuid(),
            'source_fichier' => 'manuel',
            'categorie'      => 'Client',
            'statut'         => 'gagne',
            'est_client'     => true,
            'client_depuis'  => now()->toDateString(),
        ])->saveQuietly();   // pas d'auto-projet : le client ajoutera ses projets lui-même

        return redirect()->route('prospects.show', $prospect)->with('success', 'Client créé.');
    }
}
