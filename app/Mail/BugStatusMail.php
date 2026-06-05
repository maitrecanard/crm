<?php

namespace App\Mail;

use App\Models\Bug;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de suivi d'un bug envoyé au CLIENT, au nom du support
 * (« Support TechCare Solutions »), à chaque changement d'étape.
 */
class BugStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Bug $bug) {}

    public function envelope(): Envelope
    {
        $libelle = Bug::STATUTS[$this->bug->statut] ?? $this->bug->statut;

        return new Envelope(
            from: new Address(config('crm.support.email'), config('crm.support.name')),
            subject: "[Support] {$this->bug->titre} — {$libelle}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bug-status', with: [
            'bug'     => $this->bug,
            'libelle' => Bug::STATUTS[$this->bug->statut] ?? $this->bug->statut,
            'message' => Bug::MESSAGES[$this->bug->statut] ?? '',
            'societe' => config('crm.support.name'),
        ]);
    }
}
