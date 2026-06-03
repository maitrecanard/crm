<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    protected $fillable = ['prospect_id', 'type', 'note', 'date'];

    protected $casts = [
        'date' => 'datetime',
    ];

    public const TYPES = [
        'note'     => 'Note',
        'appel'    => 'Appel',
        'email'    => 'Email',
        'linkedin' => 'LinkedIn',
        'rdv'      => 'RDV',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}
