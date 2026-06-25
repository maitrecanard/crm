<?php

namespace App\Notifications;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe le partenaire de l'avancement d'une tâche qu'il a transmise :
 * prise en charge (en_cours) ou terminée (fait). Canaux : e-mail + base (in-app).
 */
class TacheStatutPartenaire extends Notification
{
    use Queueable;

    public function __construct(public ProjectTask $tache, public string $statut) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function libelle(): string
    {
        return $this->statut === 'fait' ? 'terminée' : 'prise en charge';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projet = $this->tache->project?->titre;

        return (new MailMessage)
            ->subject('Votre tâche a été '.$this->libelle().' — '.config('crm.vendeur.societe'))
            ->greeting('Bonjour,')
            ->line('La tâche que vous avez transmise a été **'.$this->libelle().'** :')
            ->line('« '.$this->tache->titre.' »'.($projet ? ' (projet : '.$projet.')' : ''))
            ->action('Voir mes tâches', route('portail.index'))
            ->line('Merci de votre confiance.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'tache_statut_partenaire',
            'titre'    => 'Tâche '.$this->libelle().' : '.$this->tache->titre,
            'statut'   => $this->statut,
            'tache_id' => $this->tache->id,
            'url'      => '/portail',
        ];
    }
}
