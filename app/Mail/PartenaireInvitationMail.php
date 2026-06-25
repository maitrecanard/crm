<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Invitation envoyée au partenaire à la création de son compte : un lien signé
 * (expirant) lui permet de définir son mot de passe et d'activer son accès.
 */
class PartenaireInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        $from = config('crm.support.email');

        return new Envelope(
            from: new Address($from, config('crm.support.name')),
            subject: 'Activez votre accès partenaire — '.config('crm.vendeur.societe'),
        );
    }

    public function content(): Content
    {
        $url = URL::temporarySignedRoute(
            'partenaire.activation',
            now()->addDays(7),
            ['user' => $this->user->getKey()],
        );

        return new Content(view: 'emails.partenaire-invitation', with: [
            'user'    => $this->user,
            'url'     => $url,
            'societe' => config('crm.vendeur.societe'),
        ]);
    }
}
