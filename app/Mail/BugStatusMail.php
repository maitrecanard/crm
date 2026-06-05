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
        return new Envelope(
            from: new Address(config('crm.support.email'), config('crm.support.name')),
            bcc: array_values(array_filter([config('crm.support.copy')])),
            subject: "[{$this->bug->subjectPrefix()}] {$this->bug->titre} — {$this->bug->statutLabel()}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bug-status', with: [
            'bug'     => $this->bug,
            'libelle' => $this->bug->statutLabel(),
            'message' => $this->bug->clientMessage(),
            'typeLabel' => Bug::TYPES[$this->bug->type] ?? 'Intervention',
            'societe' => config('crm.support.name'),
        ]);
    }
}
