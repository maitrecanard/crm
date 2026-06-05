<?php

namespace App\Providers;

use App\Models\Prospect;
use App\Observers\ProspectObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Prospect « Gagné » -> client + projet auto.
        Prospect::observe(ProspectObserver::class);

        // E-mail de réinitialisation de mot de passe, en français.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $expire = config('auth.passwords.users.expire', 60);

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe — CRM')
                ->greeting('Bonjour,')
                ->line('Vous recevez cet e-mail car une réinitialisation de mot de '
                    .'passe a été demandée pour votre compte.')
                ->action('Réinitialiser mon mot de passe', $url)
                ->line("Ce lien expirera dans {$expire} minutes.")
                ->line("Si vous n'êtes pas à l'origine de cette demande, ignorez "
                    .'simplement cet e-mail.')
                ->salutation('— L’équipe CRM');
        });
    }
}
