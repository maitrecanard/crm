<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    protected $fillable = ['prospect_id', 'nom', 'email', 'fonction', 'notifie_tickets'];

    protected $casts = [
        'notifie_tickets' => 'boolean',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}
