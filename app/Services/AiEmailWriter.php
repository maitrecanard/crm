<?php

namespace App\Services;

use App\Models\Prospect;
use Illuminate\Support\Facades\Http;

/**
 * Rédige un e-mail de prospection professionnel à partir des données du prospect,
 * via l'API Anthropic. Prompt système imposé : expert technico-commercial senior.
 */
class AiEmailWriter
{
    private const SYSTEM = "Tu es un expert technico-commercial senior dans la tech. "
        ."Écris un e-mail de prospection professionnel, en français, prêt à être envoyé. "
        ."Personnalise-le avec les données de l'entreprise fournies. Sois concret, "
        ."centré sur la valeur pour le destinataire, sans jargon vide. "
        ."Commence par une ligne « Objet : ... », puis le corps du mail signé par le vendeur. "
        ."Pas de texte d'explication avant ou après, uniquement l'e-mail.";

    /** @return array{ok: bool, email?: string, error?: string} */
    public static function generate(Prospect $prospect): array
    {
        $key = config('crm.ai.key');
        if (! $key) {
            return ['ok' => false, 'error' => 'Clé IA absente : renseigne ANTHROPIC_API_KEY dans le .env.'];
        }

        $vendeur = config('crm.vendeur');
        $data = collect([
            'Entreprise'       => $prospect->entreprise,
            'Secteur'          => $prospect->secteur,
            'Localité'         => $prospect->localite,
            'Besoin / signal'  => $prospect->signal_alerte,
            'Catégorie'        => $prospect->categorie,
        ])->filter()->map(fn ($v, $k) => "- {$k} : {$v}")->implode("\n");

        $prompt = "Données de l'entreprise prospect :\n{$data}\n\n"
            ."Vendeur (expéditeur) : {$vendeur['prenom']}, {$vendeur['societe']}"
            .($vendeur['contact'] ? " ({$vendeur['contact']})" : '').".\n"
            ."Rédige l'e-mail de prospection.";

        try {
            $resp = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(40)->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('crm.ai.model'),
                'max_tokens' => 1024,
                'system'     => self::SYSTEM,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Appel IA impossible : '.$e->getMessage()];
        }

        if (! $resp->ok()) {
            return ['ok' => false, 'error' => 'API IA : '.$resp->status().' '.($resp->json('error.message') ?? $resp->body())];
        }

        $text = $resp->json('content.0.text');
        if (! $text) {
            return ['ok' => false, 'error' => 'Réponse IA vide.'];
        }

        return ['ok' => true, 'email' => trim($text)];
    }
}
