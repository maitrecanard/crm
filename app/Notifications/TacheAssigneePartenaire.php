<?php

namespace App\Notifications;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient un partenaire qu'une tâche lui a été assignée : il doit l'accepter
 * ou la refuser depuis son portail. Canaux : e-mail + base (in-app).
 */
class TacheAssigneePartenaire extends Notification
{
    use Queueable;

    public function __construct(public ProjectTask $tache) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projet = $this->tache->project?->titre;

        return (new MailMessage)
            ->subject('Nouvelle tâche à valider — '.config('crm.vendeur.societe'))
            ->greeting('Bonjour,')
            ->line('Une tâche vient de vous être assignée :')
            ->line('« '.$this->tache->titre.' »'.($projet ? ' (projet : '.$projet.')' : ''))
            ->line($this->tache->echeance ? 'Échéance souhaitée : '.$this->tache->echeance->format('d/m/Y').'.' : '')
            ->action('Accepter ou refuser', route('portail.index'))
            ->line('Merci d’indiquer si vous pouvez la prendre en charge.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'tache_assignee_partenaire',
            'titre'    => 'Nouvelle tâche à valider : '.$this->tache->titre,
            'tache_id' => $this->tache->id,
            'url'      => '/portail',
        ];
    }
}
