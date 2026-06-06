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
        ."centré sur la valeur pour le destinataire, sans jargon vide.\n"
        ."IMPORTANT : à partir des signaux d'analyse du site fournis, identifie UNE lacune "
        ."concrète (ex. absence de HTTPS, pas de version mobile, mentions légales/RGPD manquantes, "
        ."site daté, absence d'intégration CRM…) et utilise-la comme accroche pour montrer ta valeur. "
        ."Reste honnête : si les signaux sont absents ou incertains, formule la lacune comme une "
        ."hypothèse ou une question (« j'ai cru remarquer que… ») plutôt qu'une affirmation catégorique.\n"
        ."Commence par une ligne « Objet : ... », puis le corps du mail signé par le vendeur. "
        ."Pas de texte d'explication avant ou après, uniquement l'e-mail.";

    /** Domaines à ne PAS analyser (job boards / annuaires : pas le site du prospect). */
    private const SKIP_DOMAINS = ['free-work.com', 'boamp.fr', 'linkedin.com', 'openstreetmap.org',
        'annuaire-entreprises.data.gouv.fr', 'recherche-entreprises.api.gouv.fr'];

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

        $analyse = self::analyzeSite($prospect->source_url);

        $prompt = "Données de l'entreprise prospect :\n{$data}\n\n"
            ."Analyse du site :\n{$analyse}\n\n"
            ."Vendeur (expéditeur) : {$vendeur['prenom']}, {$vendeur['societe']}"
            .($vendeur['contact'] ? " ({$vendeur['contact']})" : '').".\n"
            ."Rédige l'e-mail : une courte analyse implicite, la lacune détectée comme accroche, "
            ."puis la proposition de valeur et un appel à un échange.";

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

    /**
     * Analyse best-effort du site du prospect : récupère la page et en extrait des
     * signaux techniques concrets pour nourrir la détection de lacune.
     */
    private static function analyzeSite(?string $url): string
    {
        if (! $url || ! preg_match('#^https?://#i', $url)) {
            return "Site non disponible — déduis une lacune plausible du secteur, formulée comme hypothèse.";
        }
        foreach (self::SKIP_DOMAINS as $dom) {
            if (stripos($url, $dom) !== false) {
                return "URL = annuaire/job board (pas le site du prospect) — déduis une lacune plausible du secteur, en hypothèse.";
            }
        }

        try {
            $resp = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; TechCareBot/1.0)'])
                ->timeout(8)->get($url);
        } catch (\Throwable $e) {
            return "Site injoignable ({$url}) — possible signe d'indisponibilité ou de site daté ; formule en hypothèse.";
        }
        if (! $resp->ok()) {
            return "Site renvoie une erreur HTTP {$resp->status()} ({$url}) — lacune de disponibilité/maintenance probable.";
        }

        $html = $resp->body();
        $low = mb_strtolower($html);
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $tm);

        $sig = [
            "URL : {$url}",
            "HTTPS : ".(str_starts_with(strtolower($url), 'https') ? 'oui' : 'NON (pas de cadenas, point bloquant)'),
            "Version mobile (balise viewport) : ".(preg_match('/<meta[^>]+name=["\']?viewport/i', $html) ? 'présente' : 'ABSENTE (site probablement non responsive)'),
            "Mentions légales : ".(str_contains($low, 'mentions') && str_contains($low, 'gales') ? 'détectées' : 'NON détectées'),
            "Bandeau cookies / RGPD : ".(str_contains($low, 'cookie') || str_contains($low, 'rgpd') || str_contains($low, 'consentement') ? 'détecté' : 'NON détecté'),
            "Prise de RDV / formulaire en ligne : ".(str_contains($low, 'rendez-vous') || str_contains($low, 'réserver') || str_contains($low, 'contact') ? 'présent' : 'non repéré'),
            "Titre de la page : ".(isset($tm[1]) ? trim(strip_tags($tm[1])) : '—'),
        ];

        return implode("\n", array_map(fn ($s) => "- {$s}", $sig));
    }
}

