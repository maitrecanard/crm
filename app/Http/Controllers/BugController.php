<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifieClientTicket;
use App\Mail\BugMessageMail;
use App\Models\Bug;
use App\Models\Project;
use Illuminate\Http\Request;

class BugController extends Controller
{
    use NotifieClientTicket;

    /** Ouverture d'un ticket depuis la fiche projet -> accusé de réception au client. */
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

        $bug = $project->bugs()->create($data + ['statut' => 'nouveau', 'source' => 'interne']);

        $res = $this->notifierStatut($bug);

        return $res['sent']
            ? back()->with('success', 'Ticket enregistré — accusé de réception envoyé au client.')
            : back()->with('error', 'Ticket enregistré, mais e-mail NON envoyé : '.$res['reason']);
    }

    /** Mise à jour : un changement de STATUT est journalisé et notifie le client. */
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

        $ancienStatut = $bug->statut;
        $statutChange = array_key_exists('statut', $data) && $data['statut'] !== $ancienStatut;

        if ($statutChange && $data['statut'] === 'livre') {
            $data['resolved_at'] = now();
        }

        $bug->update($data);

        if (! $statutChange) {
            return back()->with('success', 'Ticket mis à jour.');
        }

        $bug->logStatut($ancienStatut, $bug->statut);

        $res = $this->notifierStatut($bug);

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

        $res = $this->envoyerAuClient($bug, new BugMessageMail($bug, $message));
        if ($res['sent']) {
            $message->forceFill(['notifie_le' => now()])->save();

            return back()->with('success', 'Message ajouté et transmis au client.');
        }

        return back()->with('error', 'Message ajouté, mais e-mail NON envoyé : '.$res['reason']);
    }

    public function destroy(Bug $bug)
    {
        $bug->delete();

        // Depuis la fiche projet : retour à la fiche ; depuis la page ticket : repli sur la liste.
        return back(fallback: route('tickets.index'))->with('success', 'Ticket supprimé.');
    }
}
