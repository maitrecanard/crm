<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugMessage extends Model
{
    protected $fillable = ['bug_id', 'corps', 'interne', 'notifie_le'];

    protected $casts = [
        'interne'    => 'boolean',
        'notifie_le' => 'datetime',
    ];

    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }
}
