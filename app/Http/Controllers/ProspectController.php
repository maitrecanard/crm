<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'statut', 'source_fichier', 'secteur', 'localite', 'has_email']);

        $query = Prospect::query()
            ->when($filters['has_email'] ?? null, fn ($q) => $q->whereNotNull('email')->where('email', '<>', ''))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) =>
                $w->where('entreprise', 'like', "%{$v}%")
                  ->orWhere('localite', 'like', "%{$v}%")
                  ->orWhere('secteur', 'like', "%{$v}%")
                  ->orWhere('signal_alerte', 'like', "%{$v}%")))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['source_fichier'] ?? null, fn ($q, $v) => $q->where('source_fichier', $v))
            ->when($filters['secteur'] ?? null, fn ($q, $v) => $q->where('secteur', $v))
            ->when($filters['localite'] ?? null, fn ($q, $v) => $q->where('localite', $v));

        // Priorité de contactabilité : on remonte d'abord les prospects que l'on
        // peut réellement joindre — site web (à auditer), téléphone, email. Score
        // = nombre de canaux disponibles (0 à 3), décroissant.
        $contactScore = "(
                (CASE WHEN source_url IS NOT NULL AND source_url <> '' THEN 1 ELSE 0 END)
              + (CASE WHEN telephone IS NOT NULL AND telephone <> '' THEN 1 ELSE 0 END)
              + (CASE WHEN email IS NOT NULL AND email <> '' THEN 1 ELSE 0 END)
            )";

        $prospects = $query->orderByRaw("$contactScore DESC")
            ->orderByRaw("CASE statut
                WHEN 'rdv' THEN 0 WHEN 'relance' THEN 1 WHEN 'contacte' THEN 2
                WHEN 'a_contacter' THEN 3 WHEN 'gagne' THEN 4 ELSE 5 END")
            ->orderBy('entreprise')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Prospects/Index', [
            'prospects' => $prospects,
            'filters'   => $filters,
            'statuts'   => Prospect::STATUTS,
            'stats'     => Prospect::select('statut', DB::raw('count(*) as n'))
                            ->groupBy('statut')->pluck('n', 'statut'),
            'sources'   => Prospect::select('source_fichier')->distinct()
                            ->orderBy('source_fichier')->pluck('source_fichier'),
            'secteurs'  => Prospect::select('secteur')->whereNotNull('secteur')
                            ->distinct()->orderBy('secteur')->pluck('secteur'),
            'total'     => Prospect::count(),
        ]);
    }

    public function show(Prospect $prospect)
    {
        $prospect->load('interactions', 'tenders:id,prospect_id,acheteur,objet,date_limite,statut',
            'projects:id,prospect_id,titre,statut', 'facturesMensuelles');

        return Inertia::render('Prospects/Show', [
            'prospect'    => $prospect,
            'statuts'     => Prospect::STATUTS,
            'typeOptions' => \App\Models\Interaction::TYPES,
            'facturation' => [
                'active'     => $prospect->facturation_active,
                'jour'       => $prospect->facturation_jour,
                'debut'      => $prospect->facturation_debut?->toDateString(),
                'montant_ht' => $prospect->facturation_montant_ht,
                'libelle'    => $prospect->facturation_libelle,
                'periodes'   => $prospect->apercuFacturation(),
            ],
        ]);
    }

    /** Programme / met à jour la surveillance de facturation mensuelle du client. */
    public function updateFacturation(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'facturation_active'     => ['required', 'boolean'],
            'facturation_jour'       => ['required', 'integer', 'between:1,28'],
            'facturation_debut'      => ['nullable', 'date'],
            'facturation_montant_ht' => ['nullable', 'numeric', 'min:0'],
            'facturation_libelle'    => ['nullable', 'string', 'max:255'],
        ]);

        // Démarrer la surveillance impose un mois de début.
        if ($data['facturation_active'] && empty($data['facturation_debut'])) {
            $data['facturation_debut'] = now()->startOfMonth()->toDateString();
        }

        $prospect->update($data);

        return back()->with('success', 'Surveillance de facturation mise à jour.');
    }

    /** Saisit / met à jour la référence de facture d'un mois donné. */
    public function upsertFacture(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'periode'    => ['required', 'date'],
            'reference'  => ['nullable', 'string', 'max:255'],
            'montant_ht' => ['nullable', 'numeric', 'min:0'],
            'envoyee_le' => ['nullable', 'date'],
        ]);

        $periode = \Carbon\Carbon::parse($data['periode'])->startOfMonth()->toDateString();
        $reference = $data['reference'] ?? null;

        $prospect->facturesMensuelles()->updateOrCreate(
            ['periode' => $periode],
            [
                'reference'  => $reference,
                'montant_ht' => $data['montant_ht'] ?? $prospect->facturation_montant_ht,
                // Saisir une référence vaut envoi : on date l'envoi si non précisé.
                'envoyee_le' => filled($reference)
                    ? ($data['envoyee_le'] ?? now()->toDateString())
                    : null,
            ]
        );

        return back()->with('success', filled($reference)
            ? 'Référence de facture enregistrée.'
            : 'Référence retirée pour ce mois.');
    }

    public function update(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            // Suivi
            'statut'            => ['sometimes', 'required', 'in:'.implode(',', array_keys(Prospect::STATUTS))],
            'notes'             => ['sometimes', 'nullable', 'string'],
            'prochaine_relance' => ['sometimes', 'nullable', 'date'],
            // Coordonnées (éditables)
            'entreprise'        => ['sometimes', 'required', 'string', 'max:255'],
            'email'             => ['sometimes', 'nullable', 'email', 'max:255'],
            'telephone'         => ['sometimes', 'nullable', 'string', 'max:60'],
            'localite'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'secteur'           => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $prospect->update($data);

        return back()->with('success', 'Prospect mis à jour.');
    }

    /** Génère un e-mail de prospection par IA et l'enregistre comme scénario email. */
    public function generateEmail(Prospect $prospect)
    {
        $res = \App\Services\AiEmailWriter::generate($prospect);
        if (! $res['ok']) {
            return back()->with('error', 'Génération IA échouée : '.$res['error']);
        }

        $scenarios = $prospect->scenarios ?? [];
        $scenarios['email'] = $res['email'];
        $prospect->update(['scenarios' => $scenarios]);

        return back()->with('success', 'E-mail généré par l’IA — relis-le puis envoie-le.');
    }

    /** Envoie un e-mail de prospection au prospect, depuis le CRM (identité vendeur). */
    public function sendEmail(Request $request, Prospect $prospect)
    {
        $data = $request->validate(['corps' => ['required', 'string', 'max:10000']]);

        if (! $prospect->email) {
            return back()->with('error', 'Ce prospect n’a pas d’adresse e-mail.');
        }

        // Extrait l'objet (1re ligne « Objet : … ») du corps.
        $lines = preg_split('/\r?\n/', trim($data['corps']));
        $subject = "Prise de contact — ".config('crm.vendeur.societe');
        $body = $data['corps'];
        if (preg_match('/^\s*objet\s*:\s*(.+)/i', $lines[0], $m)) {
            $subject = trim($m[1]);
            $body = ltrim(implode("\n", array_slice($lines, 1)));
        }

        $vendeur = config('crm.vendeur');
        $fromName = trim($vendeur['prenom'].' — '.$vendeur['societe']);
        $fromEmail = $vendeur['email'] ?: config('crm.support.email');

        try {
            \Illuminate\Support\Facades\Mail::raw($body, function ($mail) use ($prospect, $subject, $fromName, $fromEmail) {
                $mail->to($prospect->email)
                    ->subject($subject)
                    ->from($fromEmail, $fromName)
                    ->replyTo($fromEmail);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'E-mail NON envoyé : '.$e->getMessage());
        }

        // Historique : on archive l'e-mail complet (objet + corps) dans le suivi.
        $prospect->interactions()->create([
            'type' => 'email',
            'note' => "📧 Email envoyé : « {$subject} »\n\n{$body}",
            'date' => now(),
        ]);

        // Passe « contacté » si encore à contacter + programme une relance à J+7.
        $relance = now()->addDays(7);
        $prospect->update([
            'statut'            => $prospect->statut === 'a_contacter' ? 'contacte' : $prospect->statut,
            'prochaine_relance' => $relance,
        ]);

        return back()->with('success', 'E-mail envoyé à '.$prospect->email
            .'. Relance programmée le '.$relance->format('d/m/Y').'.');
    }

    /** Enregistre (ou réinitialise) un scénario personnalisé pour ce prospect. */
    public function saveScenario(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'key'   => ['required', 'in:appel,email,linkedin'],
            'value' => ['nullable', 'string', 'max:5000'],
        ]);

        $scenarios = $prospect->scenarios ?? [];
        if (blank($data['value'])) {
            unset($scenarios[$data['key']]);   // réinitialisation -> retour au modèle généré
        } else {
            $scenarios[$data['key']] = $data['value'];
        }
        $prospect->update(['scenarios' => $scenarios ?: null]);

        return back(fallback: route('prospects.show', $prospect));
    }
}
