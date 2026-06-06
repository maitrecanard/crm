<?php

namespace App\Http\Controllers;

use App\Mail\BugMessageMail;
use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class BugController extends Controller
{
    /** Le client déclare un bug -> enregistré + accusé de réception par e-mail. */
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'type'               => ['required', 'in:'.implode(',', array_keys(Bug::TYPES))],
            'titre'              => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'gravite'            => ['required', 'in:'.implode(',', array_keys(Bug::GRAVITES))],
            'recurrence'         => ['nullable', 'in:'.implode(',', array_keys(Bug::RECURRENCES))],
            'prochaine_echeance' => ['nullable', 'date'],
            'issue_git'          => ['nullable', 'string', 'max:255'],
        ]);

        $bug = $project->bugs()->create($data + ['statut' => 'nouveau']);

        $res = $this->notify($bug);

        return $res['sent']
            ? back()->with('success', 'Ticket enregistré — accusé de réception envoyé au client.')
            : back()->with('error', 'Ticket enregistré, mais e-mail NON envoyé : '.$res['reason']);
    }

    /** Mise à jour : un changement de STATUT notifie le client. */
    public function update(Request $request, Bug $bug)
    {
        $data = $request->validate([
            'type'               => ['sometimes', 'required', 'in:'.implode(',', array_keys(Bug::TYPES))],
            'titre'              => ['sometimes', 'required', 'string', 'max:255'],
            'description'        => ['sometimes', 'nullable', 'string'],
            'statut'             => ['sometimes', 'required', 'in:'.implode(',', array_keys(Bug::STATUTS))],
            'gravite'            => ['sometimes', 'required', 'in:'.implode(',', array_keys(Bug::GRAVITES))],
            'recurrence'         => ['sometimes', 'nullable', 'in:'.implode(',', array_keys(Bug::RECURRENCES))],
            'prochaine_echeance' => ['sometimes', 'nullable', 'date'],
            'issue_git'          => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $statutChange = array_key_exists('statut', $data) && $data['statut'] !== $bug->statut;

        if ($statutChange && $data['statut'] === 'livre') {
            $data['resolved_at'] = now();
        }

        $bug->update($data);

        if (! $statutChange) {
            return back()->with('success', 'Ticket mis à jour.');
        }

        $res = $this->notify($bug);

        return $res['sent']
            ? back()->with('success', 'Statut mis à jour — client notifié par e-mail.')
            : back()->with('error', 'Statut mis à jour, mais e-mail NON envoyé : '.$res['reason']);
    }

    /** Ajoute un commentaire / une résolution au ticket, transmis au client SAUF si interne. */
    public function storeMessage(Request $request, Bug $bug)
    {
        $data = $request->validate([
            'corps'   => ['required', 'string', 'max:5000'],
            'interne' => ['boolean'],
        ]);
        $interne = (bool) ($data['interne'] ?? false);

        $message = $bug->messages()->create(['corps' => $data['corps'], 'interne' => $interne]);

        if ($interne) {
            return back()->with('success', 'Note interne ajoutée (non transmise au client).');
        }

        $res = $this->sendToClient($bug, new BugMessageMail($bug, $message));
        if ($res['sent']) {
            $message->forceFill(['notifie_le' => now()])->save();

            return back()->with('success', 'Message ajouté et transmis au client.');
        }

        return back()->with('error', 'Message ajouté, mais e-mail NON envoyé : '.$res['reason']);
    }

    public function destroy(Bug $bug)
    {
        $project = $bug->project_id;
        $bug->delete();

        return back(fallback: route('projects.show', $project));
    }

    /** Notifie le client d'un changement d'étape (e-mail de statut). */
    private function notify(Bug $bug): array
    {
        $res = $this->sendToClient($bug, new BugStatusMail($bug));
        if ($res['sent']) {
            $bug->forceFill(['notifie_le' => now()])->save();
        }

        return $res;
    }

    /**
     * Envoie un e-mail au client du projet (au nom du support).
     * @return array{sent: bool, reason: string}
     */
    private function sendToClient(Bug $bug, Mailable $mail): array
    {
        $bug->loadMissing('project.prospect');
        $email = $bug->project?->prospect?->email;

        if (! $email) {
            return ['sent' => false, 'reason' => 'le client n’a pas d’adresse e-mail (à renseigner sur sa fiche).'];
        }

        try {
            Mail::to($email)->send($mail);

            return ['sent' => true, 'reason' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['sent' => false, 'reason' => $e->getMessage()];
        }
    }
}
