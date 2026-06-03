<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FAQRCode\Google2FA;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, Google2FA $g2fa): Response
    {
        $user = $request->user();

        // Données 2FA : QR + clé + codes de récupération pendant la configuration.
        $twoFactor = [
            'enabled'   => $user->hasTwoFactorEnabled(),
            'pending'   => ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at),
            'qr'        => null,
            'secretKey' => null,
            'recoveryCodes' => null,
        ];
        if ($user->two_factor_secret && is_null($user->two_factor_confirmed_at)) {
            $twoFactor['qr'] = $g2fa->getQRCodeInline(config('app.name'), $user->email, $user->two_factor_secret);
            $twoFactor['secretKey'] = $user->two_factor_secret;
            $twoFactor['recoveryCodes'] = $user->two_factor_recovery_codes;
        } elseif ($twoFactor['enabled']) {
            $twoFactor['recoveryCodes'] = $user->two_factor_recovery_codes;
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'twoFactor' => $twoFactor,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
