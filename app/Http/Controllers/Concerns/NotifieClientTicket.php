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

    /** Notifie le client d'un changement d'étape (e-mail de statut) et horodate l'envoi. */
    protected function notifierStatut(Bug $bug): array
    {
        $res = $this->envoyerAuClient($bug, new BugStatusMail($bug));
        if ($res['sent']) {
            $bug->forceFill(['notifie_le' => now()])->save();
        }

        return $res;
    }
}
