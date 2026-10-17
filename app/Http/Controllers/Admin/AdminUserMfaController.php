<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class AdminUserMfaController extends Controller
{
    /**
     * Display the recovery / reset codes for the specified user (READ-ONLY).
     * Only Top-Level and Middle Management can inspect reset codes.
     */
    public function showRecoveryCodes(Request $request, User $user): JsonResponse
    {
        if (! $request->user()?->canManageMfa()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya administrator / level manajemen yang dapat melihat reset code pengguna.',
            ], 403);
        }

        $recoveryCodes = $user->recoveryCodes();

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'is_mfa_bound' => $user->isMfaBound(),
            'has_two_factor' => ! empty($user->two_factor_secret),
            'mfa_confirmed_at' => $user->two_factor_confirmed_at?->format('d M Y H:i:s'),
            'recovery_codes' => $recoveryCodes,
            'read_only' => true,
        ]);
    }

    /**
     * Reset the Two-Factor Authentication (MFA) for the specified user.
     * This revokes secret, recovery codes, and confirmed timestamp, reverting account to unactivated state.
     */
    public function resetMfa(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $request->user()?->canManageMfa()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya administrator / level manajemen yang dapat mereset MFA pengguna.',
                ], 403);
            }

            abort(403, 'Akses ditolak. Hanya administrator / level manajemen yang dapat mereset MFA pengguna.');
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'is_mfa_bound' => false,
        ])->save();

        event(new TwoFactorAuthenticationDisabled($user));

        $message = "MFA untuk pengguna {$user->name} ({$user->email}) berhasil direset. Pengguna wajib melakukan aktivasi ulang saat login.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_mfa_bound' => false,
            ]);
        }

        return back()->with('success', $message);
    }
}
