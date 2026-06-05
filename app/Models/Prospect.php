<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospect extends Model
{
    protected $fillable = [
        'cle', 'entreprise', 'localite', 'telephone', 'email', 'categorie',
        'secteur', 'signal_alerte', 'source_url', 'requete', 'source_fichier',
        'statut', 'prochaine_relance', 'notes', 'scenarios',
    ];

    protected $casts = [
        'prochaine_relance' => 'date',
        'scenarios' => 'array',
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
}
