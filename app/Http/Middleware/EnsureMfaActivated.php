<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaActivated
{
    /**
     * Handle an incoming request.
     * Ensure every authenticated user has bound & confirmed MFA (Two-Factor Authentication).
     * If MFA is not yet bound, the account is considered unactivated and the user is redirected
     * to the MFA activation setup screen.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Allow routes that are needed for MFA activation and logout
        if ($request->routeIs('mfa.*', 'logout', 'two-factor.*')) {
            return $next($request);
        }

        // If user has not activated / bound MFA, restrict access
        if (! $user->isMfaBound()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda belum diaktivasi. Wajib mengaktifkan autentikasi 2-faktor (MFA) untuk melanjutkan.',
                    'mfa_required' => true,
                ], 403);
            }

            return redirect()->route('mfa.activate')->with('warning', 'Akun Anda belum diaktivasi. Anda wajib mengaktifkan MFA untuk melanjutkan.');
        }

        return $next($request);
    }
}
