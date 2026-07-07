<?php

namespace App\Http\Controllers;

use App\Models\Besoin;
use App\Models\Devis;
use App\Models\FacturePonctuelle;
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

    /** Fiche client dédiée : infos + besoins + devis + factures (mensuel/ponctuel) + email. */
    public function show(Prospect $client)
    {
        abort_unless($client->est_client, 404);

        $client->load(
            'besoins', 'devis', 'facturesPonctuelles', 'facturesMensuelles', 'contrats',
            'projects:id,prospect_id,titre,statut', 'contacts',
        );

        return Inertia::render('Clients/Show', [
            'client'   => $client,
            'contacts' => $client->contacts,
            'support' => [
                'token'    => $client->support_token,
                'endpoint' => url('/api/support'),
            ],
            'besoins' => $client->besoins,
            'devis'   => $client->devis,
            'facturesPonctuelles' => $client->facturesPonctuelles,
            'contrats' => $client->contrats,
            'facturation' => [
                'active'     => $client->facturation_active,
                'jour'       => $client->facturation_jour,
                'debut'      => $client->facturation_debut?->toDateString(),
                'montant_ht' => $client->facturation_montant_ht,
                'libelle'    => $client->facturation_libelle,
                'periodes'   => $client->apercuFacturation(),
            ],
            'emailGenere' => $client->scenarios['email'] ?? null,
            'statutsBesoin' => Besoin::STATUTS,
            'statutsDevis'  => Devis::STATUTS,
            'statutsFacture' => FacturePonctuelle::STATUTS,
            'statutsContrat' => \App\Models\Contrat::STATUTS,
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    /** (Ré)génère le token d'assistance du client (à coller sur son site). */
    public function regenerateSupportToken(Prospect $client)
    {
        abort_unless($client->est_client, 404);
        $client->genererTokenSupport();

        return back()->with('success', 'Nouveau token d’assistance généré.');
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

        return redirect()->route('clients.show', $prospect)->with('success', 'Client créé.');
    }

    // --- Besoins -----------------------------------------------------------
    public function storeBesoin(Request $request, Prospect $client)
    {
        $data = $request->validate([
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'statut'      => ['nullable', 'in:'.implode(',', array_keys(Besoin::STATUTS))],
        ]);
        $client->besoins()->create($data + ['statut' => $data['statut'] ?? 'a_traiter']);

        return back()->with('success', 'Besoin ajouté.');
    }

    public function updateBesoin(Request $request, Besoin $besoin)
    {
        $besoin->update($request->validate([
            'titre'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'statut'      => ['sometimes', 'in:'.implode(',', array_keys(Besoin::STATUTS))],
        ]));

        return back()->with('success', 'Besoin mis à jour.');
    }

    public function destroyBesoin(Besoin $besoin)
    {
        $besoin->delete();

        return back()->with('success', 'Besoin supprimé.');
    }

    // --- Contacts (liste de diffusion) -------------------------------------
    public function storeContact(Request $request, Prospect $client)
    {
        $client->contacts()->create($this->validateContact($request));

        return back()->with('success', 'Contact ajouté.');
    }

    public function updateContact(Request $request, \App\Models\ClientContact $contact)
    {
        $contact->update($this->validateContact($request, partial: true));

        return back()->with('success', 'Contact mis à jour.');
    }

    public function destroyContact(\App\Models\ClientContact $contact)
    {
        $contact->delete();

        return back()->with('success', 'Contact supprimé.');
    }

    /** @return array<string,mixed> */
    private function validateContact(Request $request, bool $partial = false): array
    {
        $rule = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'nom'             => ['nullable', 'string', 'max:255'],
            'email'           => [$rule, 'email', 'max:255'],
            'fonction'        => ['nullable', 'string', 'max:255'],
            'notifie_tickets' => ['boolean'],
        ]);
    }

    // --- Devis -------------------------------------------------------------
    public function storeDevis(Request $request, Prospect $client)
    {
        $client->devis()->create($this->validateDevis($request));

        return back()->with('success', 'Devis ajouté.');
    }

    public function updateDevis(Request $request, Devis $devis)
    {
        $devis->update($this->validateDevis($request));

        return back()->with('success', 'Devis mis à jour.');
    }

    public function destroyDevis(Devis $devis)
    {
        $devis->delete();

        return back()->with('success', 'Devis supprimé.');
    }

    private function validateDevis(Request $request): array
    {
        return $request->validate([
            'reference'  => ['nullable', 'string', 'max:255'],
            'montant_ht' => ['nullable', 'numeric', 'min:0'],
            'statut'     => ['required', 'in:'.implode(',', array_keys(Devis::STATUTS))],
            'date_devis' => ['nullable', 'date'],
            'lien'       => ['nullable', 'string', 'max:1000'],
            'notes'      => ['nullable', 'string'],
        ]);
    }

    // --- Factures ponctuelles ---------------------------------------------
    public function storeFacture(Request $request, Prospect $client)
    {
        $client->facturesPonctuelles()->create($this->validateFacture($request));

        return back()->with('success', 'Facture ajoutée.');
    }

    public function updateFacture(Request $request, FacturePonctuelle $facture)
    {
        $facture->update($this->validateFacture($request));

        return back()->with('success', 'Facture mise à jour.');
    }

    public function destroyFacture(FacturePonctuelle $facture)
    {
        $facture->delete();

        return back()->with('success', 'Facture supprimée.');
    }

    private function validateFacture(Request $request): array
    {
        return $request->validate([
            'reference'    => ['nullable', 'string', 'max:255'],
            'montant_ht'   => ['nullable', 'numeric', 'min:0'],
            'statut'       => ['required', 'in:'.implode(',', array_keys(FacturePonctuelle::STATUTS))],
            'date_facture' => ['nullable', 'date'],
            'lien'         => ['nullable', 'string', 'max:1000'],
            'notes'        => ['nullable', 'string'],
        ]);
    }
}
