<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class GoogleMfaController extends Controller
{
    /**
     * Display the Google MFA setup page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('google_mfa_pending_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget('google_mfa_pending_user_id');
            return redirect()->route('login');
        }

        if (empty($user->two_factor_secret)) {
            app(EnableTwoFactorAuthentication::class)($user);
            $user->refresh();
        }

        return Inertia::render('auth/GoogleMfaSetup', [
            'qrCodeSvg' => $user->twoFactorQrCodeSvg(),
            'secretKey' => decrypt($user->two_factor_secret),
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    /**
     * Verify and confirm the Google MFA authenticator code.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $userId = $request->session()->get('google_mfa_pending_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || empty($user->two_factor_secret)) {
            $request->session()->forget('google_mfa_pending_user_id');
            return redirect()->route('login');
        }

        $twoFactorProvider = app(TwoFactorAuthenticationProvider::class);

        $valid = $twoFactorProvider->verify(
            decrypt($user->two_factor_secret),
            $request->code
        );

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'Kode autentikator tidak valid atau telah kedaluwarsa.',
            ]);
        }

        $user->two_factor_confirmed_at = now();
        $user->is_mfa_bound = true;
        $user->save();

        $request->session()->forget('google_mfa_pending_user_id');

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
