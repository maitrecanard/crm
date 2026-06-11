<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Paramètres globaux clé/valeur (ex. modèle de conditions de contrat). */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
