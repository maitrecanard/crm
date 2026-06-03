<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private Google2FA $g2fa) {}

    /** Affiche la page de challenge (l'utilisateur a passé l'étape mot de passe). */
    public function create(Request $request)
    {
        if (! $request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /** Vérifie le code TOTP ou un code de récupération, puis connecte. */
    public function store(Request $request)
    {
        $userId = $request->session()->get('2fa:user:id');
        if (! $userId) {
            return redirect()->route('login');
        }
        $user = User::findOrFail($userId);

        $code = trim((string) $request->input('code'));
        $recovery = trim((string) $request->input('recovery_code'));

        $ok = false;
        if ($code !== '' && $this->g2fa->verifyKey($user->two_factor_secret, $code)) {
            $ok = true;
        } elseif ($recovery !== '') {
            $codes = $user->two_factor_recovery_codes ?? [];
            if (in_array($recovery, $codes, true)) {
                // Consomme le code de récupération.
                $user->forceFill([
                    'two_factor_recovery_codes' => array_values(array_diff($codes, [$recovery])),
                ])->save();
                $ok = true;
            }
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'code' => 'Code à deux facteurs invalide.',
            ]);
        }

        $request->session()->forget('2fa:user:id');
        Auth::login($user, $request->session()->get('2fa:remember', false));
        $request->session()->forget('2fa:remember');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
