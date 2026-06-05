<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use Illuminate\Http\Request;
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
}
