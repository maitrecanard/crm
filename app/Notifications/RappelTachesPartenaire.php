<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Rappel quotidien des tâches transmises par les partenaires et encore à faire.
 * Diffusé sur deux canaux : e-mail + base de données (badge in-app).
 */
class RappelTachesPartenaire extends Notification
{
    use Queueable;

    /** @param Collection<int,\App\Models\ProjectTask> $taches */
    public function __construct(public Collection $taches) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $n = $this->taches->count();
        $enRetard = $this->taches->filter(
            fn ($t) => $t->echeance && $t->echeance->isPast()
        )->count();

        $mail = (new MailMessage)
            ->subject("[CRM] {$n} tâche(s) partenaire à réaliser")
            ->greeting('Bonjour,')
            ->line("Vous avez {$n} tâche(s) transmise(s) par vos partenaires et non terminée(s)"
                .($enRetard ? " (dont {$enRetard} en retard)" : '').' :');

        foreach ($this->taches->take(20) as $t) {
            $ech = $t->echeance ? ' — échéance '.$t->echeance->format('d/m/Y') : '';
            $part = $t->partenaire?->nom ? ' ['.$t->partenaire->nom.']' : '';
            $mail->line('• '.$t->titre.$ech.$part);
        }

        if ($n > 20) {
            $mail->line('… et '.($n - 20).' autre(s).');
        }

        return $mail
            ->action('Ouvrir le CRM', url('/dashboard'))
            ->line('Bonne journée !');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'rappel_taches_partenaire',
            'count'      => $this->taches->count(),
            'titre'      => $this->taches->count().' tâche(s) partenaire à réaliser',
            'tache_ids'  => $this->taches->pluck('id')->all(),
            'url'        => '/dashboard',
        ];
    }
}
