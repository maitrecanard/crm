<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suivi mensuel de facturation d'un client. Une ligne = un mois (`periode`).
 * La `reference` (n° de facture saisi par l'utilisateur) vaut preuve d'envoi :
 * absente passé l'échéance → la facture est « en retard » et déclenche une alerte.
 */
class FactureMensuelle extends Model
{
    protected $table = 'factures_mensuelles';

    protected $fillable = [
        'prospect_id', 'periode', 'reference', 'montant_ht',
        'envoyee_le', 'alerte_envoyee_le', 'notes',
    ];

    protected $casts = [
        'periode' => 'date',
        'envoyee_le' => 'date',
        'alerte_envoyee_le' => 'datetime',
        'montant_ht' => 'decimal:2',
    ];

    protected $appends = ['est_envoyee', 'en_retard', 'echeance', 'mois_label'];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** Jour d'échéance (mois suivant la période) pour un client donné. */
    public static function echeancePour(Carbon $periode, int $jour): Carbon
    {
        return $periode->copy()->startOfMonth()->addMonthNoOverflow()->day($jour);
    }

    public function getEstEnvoyeeAttribute(): bool
    {
        return filled($this->reference);
    }

    public function getEcheanceAttribute(): ?Carbon
    {
        $jour = $this->prospect?->facturation_jour ?? 5;
        return $this->periode ? self::echeancePour($this->periode, $jour) : null;
    }

    public function getEnRetardAttribute(): bool
    {
        return ! $this->est_envoyee && $this->echeance && now()->greaterThan($this->echeance);
    }

    public function getMoisLabelAttribute(): string
    {
        return $this->periode ? $this->periode->translatedFormat('F Y') : '';
    }
}
