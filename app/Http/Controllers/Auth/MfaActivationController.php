<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class MfaActivationController extends Controller
{
    /**
     * Display the mandatory MFA activation page for the authenticated user.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // If the user is already bound and confirmed, redirect them directly to dashboard
        if ($user->isMfaBound()) {
            return redirect()->route('dashboard');
        }

        // Ensure 2FA secret and recovery codes are generated
        if (empty($user->two_factor_secret)) {
            app(EnableTwoFactorAuthentication::class)($user);
            $user->refresh();
        }

        return Inertia::render('mfa/Activate', [
            'qrCodeSvg' => $user->twoFactorQrCodeSvg(),
            'secretKey' => decrypt($user->two_factor_secret),
            'email' => $user->email,
            'name' => $user->name,
            'is_activated' => false,
        ]);
    }

    /**
     * Verify and confirm the TOTP code to activate the user's account with MFA.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (empty($user->two_factor_secret)) {
            app(EnableTwoFactorAuthentication::class)($user);
            $user->refresh();
        }

        $twoFactorProvider = app(TwoFactorAuthenticationProvider::class);

        $valid = $twoFactorProvider->verify(
            decrypt($user->two_factor_secret),
            $request->code
        );

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'Kode autentikator 6-digit tidak valid atau telah kedaluwarsa. Silakan periksa kembali aplikasi autentikator Anda.',
            ]);
        }

        // Mark user as active and MFA bound
        $user->two_factor_confirmed_at = now();
        $user->is_mfa_bound = true;
        $user->save();

        return redirect()->intended(route('dashboard'))->with('success', 'Selamat! Akun Anda telah berhasil diaktivasi dengan MFA.');
    }
}
