<?php

namespace App\Notifications;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe l'admin de la réponse d'un partenaire à une tâche assignée :
 * acceptée, refusée (avec motif) ou terminée. Canaux : e-mail + base (in-app).
 */
class ReponseTachePartenaire extends Notification
{
    use Queueable;

    /** @param 'acceptee'|'refusee'|'fait' $reponse */
    public function __construct(public ProjectTask $tache, public string $reponse) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function libelle(): string
    {
        return ['acceptee' => 'acceptée', 'refusee' => 'refusée', 'fait' => 'terminée'][$this->reponse] ?? $this->reponse;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $partenaire = $this->tache->partenaire?->nom ?? 'Le partenaire';
        $projet = $this->tache->project?->titre;

        $mail = (new MailMessage)
            ->subject('Tâche '.$this->libelle().' par '.$partenaire.' — '.config('crm.vendeur.societe'))
            ->greeting('Bonjour,')
            ->line($partenaire.' a **'.$this->libelle().'** la tâche :')
            ->line('« '.$this->tache->titre.' »'.($projet ? ' (projet : '.$projet.')' : ''));

        if ($this->reponse === 'refusee' && $this->tache->motif_refus) {
            $mail->line('Motif du refus : '.$this->tache->motif_refus);
        }

        return $mail
            ->action('Voir le projet', route('projects.show', $this->tache->project_id))
            ->line('Vous pouvez la réassigner si besoin.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'reponse_tache_partenaire',
            'titre'    => 'Tâche '.$this->libelle().' : '.$this->tache->titre,
            'reponse'  => $this->reponse,
            'tache_id' => $this->tache->id,
            'url'      => '/projets/'.$this->tache->project_id,
        ];
    }
}
