<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $googleId = (string) $googleUser->getId();
            $rawEmail = $googleUser->getEmail();
            $normalizedEmail = User::normalizeEmail($rawEmail);

            // 1. Primary Match: Match by immutable Google ID
            $user = User::where('google_id', $googleId)->first();

            // 2. Secondary Match: Match by exact or normalized email address to prevent Gmail alias bypasses
            if (! $user) {
                $allUsers = User::all();
                $user = $allUsers->first(function (User $u) use ($rawEmail, $normalizedEmail) {
                    return strtolower($u->email) === strtolower($rawEmail)
                        || User::normalizeEmail($u->email) === $normalizedEmail;
                });
            }

            if (! $user) {
                // Reject unregistered emails back to login page with credential error
                return redirect()->route('login')->withErrors([
                    'email' => 'Gagal login via Google: Email Anda tidak terdaftar dalam sistem kredensial.',
                ]);
            }

            // Update existing registered user with Google ID, normalized email, and avatar
            $user->update([
                'google_id' => $user->google_id ?? $googleId,
                'avatar' => $user->avatar ?? $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            if ($user->isMfaBound()) {
                // User has already bound MFA/Authenticator. Prompt for 2FA challenge.
                $request->session()->put([
                    'login.id' => $user->getKey(),
                    'login.remember' => true,
                ]);

                return redirect()->route('two-factor.login');
            }

            // User hasn't bound MFA yet. Force Authenticator binding step before login.
            $request->session()->put('google_mfa_pending_user_id', $user->id);

            return redirect()->route('auth.google.mfa-setup');
        } catch (Throwable $e) {
            return redirect()->route('login')->with('status', 'Gagal melakukan otentikasi via Google. Silakan coba lagi.');
        }
    }
}
