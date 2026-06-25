<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password', 'google_id', 'avatar', 'role', 'partenaire_id'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'google_id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            // Chiffré avec la clé d'application (APP_KEY), AES-256.
            'google_id' => 'encrypted',
        ];
    }

    /** Le 2FA est activé ET confirmé. */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /** Partenaire rattaché (NULL pour les comptes admin). */
    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    /** Compte partenaire (accès limité au portail). */
    public function isPartenaire(): bool
    {
        return $this->role === 'partenaire';
    }

    /** Compte admin (accès complet au CRM). */
    public function isAdmin(): bool
    {
        return $this->role !== 'partenaire';
    }
}
