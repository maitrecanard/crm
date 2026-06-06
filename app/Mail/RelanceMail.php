<?php

namespace App\Mail;

use App\Models\Prospect;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** E-mail de relance automatique d'un prospect contacté (identité vendeur). */
class RelanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Prospect $prospect) {}

    public function envelope(): Envelope
    {
        $vendeur = config('crm.vendeur');
        $replyTo = $vendeur['email'] ?: config('crm.support.email');

        return new Envelope(
            from: new Address(config('crm.support.email'), trim($vendeur['prenom'].' — '.$vendeur['societe'])),
            replyTo: [new Address($replyTo)],
            subject: 'Suite à mon précédent message — '.$vendeur['societe'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.relance', with: [
            'prospect' => $this->prospect,
            'vendeur'  => config('crm.vendeur'),
        ]);
    }
}
