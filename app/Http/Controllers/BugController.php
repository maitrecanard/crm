<?php

namespace App\Http\Controllers;

use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BugController extends Controller
{
    /** Le client déclare un bug -> enregistré + accusé de réception par e-mail. */
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'gravite'     => ['required', 'in:'.implode(',', array_keys(Bug::GRAVITES))],
            'issue_git'   => ['nullable', 'string', 'max:255'],
        ]);

        $bug = $project->bugs()->create($data + ['statut' => 'nouveau']);

        $sent = $this->notify($bug);

        return back()->with('success', $sent
            ? 'Bug enregistré — accusé de réception envoyé au client.'
            : 'Bug enregistré (client non notifié : aucune adresse e-mail).');
    }

    /** Mise à jour : un changement de STATUT notifie le client. */
    public function update(Request $request, Bug $bug)
    {
        $data = $request->validate([
            'titre'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'statut'      => ['sometimes', 'required', 'in:'.implode(',', array_keys(Bug::STATUTS))],
            'gravite'     => ['sometimes', 'required', 'in:'.implode(',', array_keys(Bug::GRAVITES))],
            'issue_git'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $statutChange = array_key_exists('statut', $data) && $data['statut'] !== $bug->statut;

        if ($statutChange && $data['statut'] === 'livre') {
            $data['resolved_at'] = now();
        }

        $bug->update($data);

        $sent = false;
        if ($statutChange) {
            $sent = $this->notify($bug);
        }

        return back()->with('success', $statutChange
            ? ($sent ? 'Statut mis à jour — client notifié par e-mail.' : 'Statut mis à jour (client non notifié : aucune adresse e-mail).')
            : 'Bug mis à jour.');
    }

    public function destroy(Bug $bug)
    {
        $project = $bug->project_id;
        $bug->delete();

        return back(fallback: route('projects.show', $project));
    }

    /** Envoie l'e-mail de suivi au client (au nom du support). Renvoie true si envoyé. */
    private function notify(Bug $bug): bool
    {
        $email = $bug->project?->prospect?->email
            ?: $bug->loadMissing('project.prospect')->project?->prospect?->email;

        if (! $email) {
            return false;
        }

        try {
            Mail::to($email)->send(new BugStatusMail($bug));
            $bug->forceFill(['notifie_le' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
