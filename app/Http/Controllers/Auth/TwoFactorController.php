<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct(private Google2FA $g2fa) {}

    /** Démarre l'activation : génère un secret + codes de récupération (non confirmé). */
    public function enable(Request $request)
    {
        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => $this->g2fa->generateSecretKey(),
            'two_factor_recovery_codes' => collect(range(1, 8))
                ->map(fn () => Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5)))
                ->all(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return back();
    }

    /** Confirme l'activation avec un code de l'application d'authentification. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $user->two_factor_secret ||
            ! $this->g2fa->verifyKey($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide. Réessaie avec le code courant de ton application.',
            ]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return back()->with('status', 'two-factor-confirmed');
    }

    /** Désactive le 2FA. */
    public function disable(Request $request)
    {
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'two-factor-disabled');
    }
}
