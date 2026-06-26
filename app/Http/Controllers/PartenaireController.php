<?php

namespace App\Http\Controllers;

use App\Mail\PartenaireInvitationMail;
use App\Models\Partenaire;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PartenaireController extends Controller
{
    public function index(Request $request)
    {
        $partenaires = Partenaire::query()
            ->with('user:id,partenaire_id,email_verified_at')
            ->withCount(['projects', 'tasks'])
            ->when($request->input('q'), fn ($q, $v) => $q
                ->where(fn ($w) => $w->where('nom', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->orderBy('nom')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Partenaires/Index', [
            'partenaires' => $partenaires,
            'filters'     => $request->only('q'),
            'total'       => Partenaire::count(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Partenaires/Create');
    }

    /** Crée le partenaire + son compte, et envoie l'e-mail d'activation. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'         => ['required', 'string', 'max:255'],
            'contact_nom' => ['nullable', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:partenaires,email', 'unique:users,email'],
            'telephone'   => ['nullable', 'string', 'max:60'],
            'notes'       => ['nullable', 'string'],
        ]);

        $partenaire = Partenaire::create($data + ['actif' => true]);

        $user = $this->createAccount($partenaire);
        $sent = $this->sendInvitation($user);

        return redirect()->route('partenaires.show', $partenaire)->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Partenaire créé. E-mail d’activation envoyé à '.$partenaire->email.'.'
                : 'Partenaire créé, mais l’e-mail d’activation n’a pas pu être envoyé. Utilisez « Renvoyer l’invitation ».',
        );
    }

    public function show(Partenaire $partenaire)
    {
        $partenaire->load([
            'user:id,partenaire_id,email,email_verified_at',
            'projects:id,partenaire_id,prospect_id,titre,statut',
            'projects.prospect:id,entreprise',
        ]);

        $taches = ProjectTask::where('partenaire_id', $partenaire->id)
            ->with('project:id,titre')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Partenaires/Show', [
            'partenaire'     => $partenaire,
            'compteActif'    => $partenaire->compte_actif,
            'taches'         => $taches,
            'statutsTache'   => ProjectTask::STATUTS,
            // Projets rattachables (sans partenaire pour l'instant).
            'projetsLibres'  => Project::whereNull('partenaire_id')
                ->with('prospect:id,entreprise')
                ->orderByDesc('id')->get(['id', 'prospect_id', 'titre', 'statut']),
        ]);
    }

    public function update(Request $request, Partenaire $partenaire)
    {
        $data = $request->validate([
            'nom'         => ['required', 'string', 'max:255'],
            'contact_nom' => ['nullable', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:partenaires,email,'.$partenaire->id],
            'telephone'   => ['nullable', 'string', 'max:60'],
            'notes'       => ['nullable', 'string'],
            'actif'       => ['boolean'],
        ]);

        $partenaire->update($data);
        // Garder l'e-mail du compte de connexion synchronisé.
        $partenaire->user?->update(['email' => $partenaire->email]);

        return back()->with('success', 'Partenaire mis à jour.');
    }

    public function destroy(Partenaire $partenaire)
    {
        $partenaire->delete();   // détache projets/tâches (nullOnDelete), supprime le compte

        return redirect()->route('partenaires.index')->with('success', 'Partenaire supprimé.');
    }

    /** Renvoyer l'e-mail d'activation (lien expiré / non reçu). */
    public function resendInvite(Partenaire $partenaire)
    {
        $user = $partenaire->user ?: $this->createAccount($partenaire);
        $sent = $this->sendInvitation($user);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'E-mail d’activation renvoyé à '.$partenaire->email.'.'
                : 'L’e-mail n’a pas pu être envoyé. Réessayez plus tard.',
        );
    }

    /** Rattacher un projet existant au partenaire. */
    public function attachProject(Request $request, Partenaire $partenaire)
    {
        $data = $request->validate(['project_id' => ['required', 'exists:projects,id']]);
        Project::whereKey($data['project_id'])->update(['partenaire_id' => $partenaire->id]);

        return back()->with('success', 'Projet rattaché au partenaire.');
    }

    /** Détacher un projet du partenaire. */
    public function detachProject(Partenaire $partenaire, Project $project)
    {
        if ($project->partenaire_id === $partenaire->id) {
            $project->update(['partenaire_id' => null]);
        }

        return back()->with('success', 'Projet détaché.');
    }

    /** Crée le compte de connexion (role partenaire, mot de passe à définir). */
    private function createAccount(Partenaire $partenaire): User
    {
        return User::create([
            'name'          => $partenaire->contact_nom ?: $partenaire->nom,
            'email'         => $partenaire->email,
            'password'      => Str::random(40),   // jamais utilisé : remplacé à l'activation
            'role'          => 'partenaire',
            'partenaire_id' => $partenaire->id,
        ]);
    }

    /** Envoie l'invitation ; renvoie false (sans planter) si le SMTP échoue. */
    private function sendInvitation(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new PartenaireInvitationMail($user));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
