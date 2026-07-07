<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Relance quotidienne (matin/soir) des tickets en cours, adressée à l'admin.
 * Canaux : e-mail + base de données (badge in-app).
 */
class RelanceTickets extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int,\App\Models\Bug>  $tickets
     * @param  'matin'|'soir'  $moment
     */
    public function __construct(public Collection $tickets, public string $moment = 'matin') {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function salutation(): string
    {
        return $this->moment === 'soir' ? 'Bonsoir,' : 'Bonjour,';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $n = $this->tickets->count();
        $bloquants = $this->tickets->where('gravite', 'bloquant')->count();

        $mail = (new MailMessage)
            ->subject('[CRM] Relance '.($this->moment === 'soir' ? 'du soir' : 'du matin')." — {$n} ticket(s) en cours")
            ->greeting($this->salutation())
            ->line("Vous avez {$n} ticket(s) en cours"
                .($bloquants ? " (dont {$bloquants} bloquant·s)" : '').' :');

        foreach ($this->tickets->take(20) as $t) {
            $client = $t->project?->prospect?->entreprise;
            $mail->line('• '.$t->reference.' — '.$t->titre
                .($client ? ' ['.$client.']' : '')
                .' — '.$t->statutLabel().' ('.$t->gravite.')');
        }

        if ($n > 20) {
            $mail->line('… et '.($n - 20).' autre(s).');
        }

        return $mail
            ->action('Voir les tickets', url('/tickets'))
            ->line($this->moment === 'soir' ? 'Bonne soirée !' : 'Bonne journée !');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'relance_tickets',
            'moment'     => $this->moment,
            'count'      => $this->tickets->count(),
            'titre'      => $this->tickets->count().' ticket(s) en cours',
            'ticket_ids' => $this->tickets->pluck('id')->all(),
            'url'        => '/tickets',
        ];
    }
}
