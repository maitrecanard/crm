<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\BugStatusMail;
use App\Models\Bug;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi d'e-mails au client d'un ticket (au nom du support). Partagé par les
 * contrôleurs qui manipulent des tickets (Bug) côté admin.
 */
trait NotifieClientTicket
{
    /**
     * Envoie un e-mail au client du projet lié au ticket.
     *
     * @return array{sent: bool, reason: string}
     */
    protected function envoyerAuClient(Bug $bug, Mailable $mail): array
    {
        $bug->loadMissing('project.prospect.contacts');
        $destinataires = $bug->project?->prospect?->destinatairesTickets() ?? [];

        if (empty($destinataires)) {
            return ['sent' => false, 'reason' => 'aucun destinataire (renseigne l’e-mail du client ou un contact abonné aux tickets).'];
        }

        try {
            Mail::to($destinataires)->send($mail);

            return ['sent' => true, 'reason' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['sent' => false, 'reason' => $e->getMessage()];
        }
    }

    /** Notifie le client d'un changement d'étape (e-mail de statut) et horodate l'envoi. */
    protected function notifierStatut(Bug $bug): array
    {
        $res = $this->envoyerAuClient($bug, new BugStatusMail($bug));
        if ($res['sent']) {
            $bug->forceFill(['notifie_le' => now()])->save();
        }

        return $res;
    }

    /**
     * Informe les partenaires liés au projet du ticket (ouverture ou changement
     * de statut). Indépendant du client : s'applique aussi aux tickets internes.
     *
     * @param  'nouveau'|'statut'  $evenement
     */
    protected function notifierPartenaires(Bug $bug, string $evenement): void
    {
        if (! $bug->project_id) {
            return;
        }

        $bug->loadMissing('project.partenaires.user');

        foreach ($bug->project?->partenaires ?? [] as $partenaire) {
            $partenaire->user?->notify(new \App\Notifications\TicketPartenaire($bug, $evenement));
        }
    }
}
