<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Prospect extends Model
{
    protected $fillable = [
        'cle', 'entreprise', 'localite', 'telephone', 'email', 'categorie',
        'secteur', 'signal_alerte', 'source_url', 'requete', 'source_fichier',
        'statut', 'prochaine_relance', 'notes', 'scenarios', 'est_client', 'client_depuis',
        'facturation_active', 'facturation_jour', 'facturation_debut',
        'facturation_montant_ht', 'facturation_libelle',
    ];

    protected $casts = [
        'prochaine_relance' => 'date',
        'scenarios' => 'array',
        'est_client' => 'boolean',
        'client_depuis' => 'date',
        'facturation_active' => 'boolean',
        'facturation_jour' => 'integer',
        'facturation_debut' => 'date',
        'facturation_montant_ht' => 'decimal:2',
    ];

    /** Étapes du pipeline commercial. */
    public const STATUTS = [
        'a_contacter' => 'À contacter',
        'contacte'    => 'Contacté',
        'relance'     => 'Relancé',
        'rdv'         => 'RDV',
        'gagne'       => 'Gagné',
        'perdu'       => 'Perdu',
        'ignore'      => 'Ignoré',
    ];

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class)->latest('date');
    }

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class)->orderBy('date_limite');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->latest();
    }

    /** Tous les tickets d'assistance du client (à travers ses projets). */
    public function supportTickets()
    {
        return Bug::whereIn('project_id', $this->projects()->select('id'));
    }

    /** (Re)génère le token d'assistance (150 caractères) et le persiste. */
    public function genererTokenSupport(): string
    {
        do {
            $token = Str::random(150);
        } while (static::where('support_token', $token)->exists());

        $this->forceFill(['support_token' => $token])->save();

        return $token;
    }

    public function facturesMensuelles(): HasMany
    {
        return $this->hasMany(FactureMensuelle::class)->orderByDesc('periode');
    }

    public function besoins(): HasMany
    {
        return $this->hasMany(Besoin::class)->latest();
    }

    public function devis(): HasMany
    {
        return $this->hasMany(Devis::class)->orderByDesc('date_devis')->latest();
    }

    public function facturesPonctuelles(): HasMany
    {
        return $this->hasMany(FacturePonctuelle::class)->orderByDesc('date_facture')->latest();
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class)->latest();
    }

    /** Liste des mois concernés, du 1er mois de facturation au mois courant. */
    public function periodesAttendues(): array
    {
        if (! $this->facturation_active || ! $this->facturation_debut) {
            return [];
        }
        $periodes = [];
        $curseur = $this->facturation_debut->copy()->startOfMonth();
        $fin = now()->startOfMonth();
        while ($curseur->lessThanOrEqualTo($fin)) {
            $periodes[] = $curseur->copy();
            $curseur->addMonthNoOverflow();
        }
        return $periodes;
    }

    /**
     * Aperçu de la facturation : un élément par mois attendu, avec sa référence
     * (si saisie), son échéance et son statut (envoyee | a_venir | en_retard).
     */
    public function apercuFacturation(): array
    {
        $existantes = $this->relationLoaded('facturesMensuelles')
            ? $this->facturesMensuelles
            : $this->facturesMensuelles()->get();
        $parPeriode = $existantes->keyBy(fn ($f) => $f->periode->format('Y-m'));

        $apercu = [];
        foreach ($this->periodesAttendues() as $periode) {
            $cle = $periode->format('Y-m');
            $f = $parPeriode->get($cle);
            $echeance = FactureMensuelle::echeancePour($periode, $this->facturation_jour ?? 5);
            $envoyee = $f && filled($f->reference);
            $statut = $envoyee ? 'envoyee' : (now()->greaterThan($echeance) ? 'en_retard' : 'a_venir');
            $apercu[] = [
                'periode'     => $cle,
                'mois_label'  => $periode->translatedFormat('F Y'),
                'reference'   => $f?->reference,
                'montant_ht'  => $f?->montant_ht ?? $this->facturation_montant_ht,
                'envoyee_le'  => $f?->envoyee_le?->toDateString(),
                'echeance'    => $echeance->toDateString(),
                'statut'      => $statut,
            ];
        }
        // Du mois le plus récent au plus ancien.
        return array_reverse($apercu);
    }

    /** Mois en retard (non envoyés, échéance dépassée). */
    public function facturesEnRetard(): array
    {
        return array_values(array_filter(
            $this->apercuFacturation(),
            fn ($m) => $m['statut'] === 'en_retard'
        ));
    }

    /** Clients dont au moins une facture mensuelle est en retard (pour le dashboard). */
    public static function alertesFacturation(): Collection
    {
        return static::where('facturation_active', true)
            ->whereNotNull('facturation_debut')
            ->with('facturesMensuelles')
            ->get()
            ->flatMap(function (Prospect $c) {
                return collect($c->facturesEnRetard())->map(fn ($m) => [
                    'prospect_id' => $c->id,
                    'entreprise'  => $c->entreprise,
                    'libelle'     => $c->facturation_libelle,
                ] + $m);
            })
            ->sortBy('echeance')
            ->values();
    }
}
