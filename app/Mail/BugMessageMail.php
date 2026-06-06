<?php

namespace App\Mail;

use App\Models\Bug;
use App\Models\BugMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Transmet un commentaire de ticket au client, au nom du support. */
class BugMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Bug $bug, public BugMessage $msg) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('crm.support.email'), config('crm.support.name')),
            bcc: array_values(array_filter([config('crm.support.copy')])),
            subject: "[{$this->bug->subjectPrefix()}] {$this->bug->titre} — Mise à jour",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bug-message', with: [
            'bug'     => $this->bug,
            'corps'   => $this->msg->corps,
            'societe' => config('crm.support.name'),
        ]);
    }
}
