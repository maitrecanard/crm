<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CrmApiToken extends Command
{
    protected $signature = 'crm:api-token {--email= : Email du compte propriétaire du token} {--name=engine : Nom du token}';

    protected $description = 'Génère un token API (Sanctum) pour pousser des prospects vers le CRM.';

    public function handle(): int
    {
        $email = $this->option('email') ?: User::query()->value('email');
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("Aucun utilisateur pour l'email : {$email}");

            return self::FAILURE;
        }

        $token = $user->createToken($this->option('name'), ['prospects:write']);

        $this->info('Token créé pour '.$user->email.' (à copier, non récupérable ensuite) :');
        $this->newLine();
        $this->line('  '.$token->plainTextToken);
        $this->newLine();
        $this->comment('À mettre dans CRM_TOKEN côté moteur (avec CRM_URL).');

        return self::SUCCESS;
    }
}
