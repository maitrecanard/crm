<?php

namespace App\Notifications;

use App\Models\Bug;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe un partenaire lié au projet qu'un ticket a été ouvert, ou que son
 * statut a changé. Canaux : e-mail + base (badge in-app du portail).
 */
class TicketPartenaire extends Notification
{
    use Queueable;

    /** @param 'nouveau'|'statut' $evenement */
    public function __construct(public Bug $bug, public string $evenement) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function intro(): string
    {
        return $this->evenement === 'nouveau'
            ? 'Un nouveau ticket a été ouvert'
            : 'Le statut d’un ticket a évolué';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projet = $this->bug->project?->titre;

        return (new MailMessage)
            ->subject('[Ticket] '.$this->bug->reference.' — '.$this->bug->titre.' — '.$this->bug->statutLabel())
            ->greeting('Bonjour,')
            ->line($this->intro().($projet ? ' sur le projet « '.$projet.' »' : '').' :')
            ->line('**'.$this->bug->reference.'** — '.$this->bug->titre)
            ->line('Statut : '.$this->bug->statutLabel().($this->bug->gravite ? ' (gravité : '.$this->bug->gravite.')' : ''))
            ->action('Ouvrir mon espace', url('/portail'))
            ->line('Merci de votre collaboration.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'ticket_partenaire',
            'evenement' => $this->evenement,
            'titre'     => 'Ticket '.$this->bug->reference.' — '.$this->bug->statutLabel(),
            'bug_id'    => $this->bug->id,
            'url'       => '/portail',
        ];
    }
}
