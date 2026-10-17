<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property string|null $company_name
 * @property int|null $manager_id
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $avatar
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property bool $is_mfa_bound
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['uuid', 'name', 'email', 'password', 'google_id', 'avatar', 'role', 'company_name', 'manager_id', 'is_mfa_bound'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (User $user) {
            $user->is_mfa_bound = ! empty($user->two_factor_confirmed_at);
        });
    }

    /**
     * Determine if MFA (Authenticator app) is bound for this user.
     */
    public function isMfaBound(): bool
    {
        return (bool) ($this->is_mfa_bound || ! empty($this->two_factor_confirmed_at));
    }

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
            'two_factor_confirmed_at' => 'datetime',
            'is_mfa_bound' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Normalize email address to neutralize Gmail dot and plus aliases.
     * e.g., raihanrivana.08+test@gmail.com -> raihanrivana08@gmail.com
     */
    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return $email;
        }

        [$username, $domain] = $parts;

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            // Remove + suffix (plus alias)
            $username = explode('+', $username, 2)[0];
            // Remove dots (dot alias)
            $username = str_replace('.', '', $username);
            return $username . '@gmail.com';
        }

        return $email;
    }

    /**
     * Linked Employee profile for this user.
     */
    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get dynamic company branding data for smart logo resolution.
     *
     * @return array<string, mixed>|null
     */
    public function getCompanyData(): ?array
    {
        // 1. Try via linked employee profile company
        if ($this->employee && $this->employee->company_id) {
            $company = Company::find($this->employee->company_id);
            if ($company) {
                return [
                    'nama_perusahaan' => $company->nama_perusahaan,
                    'logo_url' => $company->logo_url,
                    'kode_perusahaan' => $company->kode_perusahaan,
                    'primary_color' => $company->primary_color,
                    'secondary_color' => $company->secondary_color,
                ];
            }
        }

        // 2. Try via user company_name matching Company model
        if (! empty($this->company_name)) {
            $company = Company::where('nama_perusahaan', $this->company_name)->first();
            if ($company) {
                return [
                    'nama_perusahaan' => $company->nama_perusahaan,
                    'logo_url' => $company->logo_url,
                    'kode_perusahaan' => $company->kode_perusahaan,
                    'primary_color' => $company->primary_color,
                    'secondary_color' => $company->secondary_color,
                ];
            }

            return [
                'nama_perusahaan' => $this->company_name,
                'logo_url' => null,
                'kode_perusahaan' => null,
                'primary_color' => null,
                'secondary_color' => null,
            ];
        }

        return null;
    }

    /**
     * Direct manager / supervisor relationship.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Subordinates reporting directly to this user.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Role checks.
     */
    public function isStaf(): bool
    {
        return $this->role === UserRole::STAF_OPERASIONAL;
    }

    public function isFirstLevel(): bool
    {
        return $this->role === UserRole::FIRST_LEVEL;
    }

    public function isMiddleLevel(): bool
    {
        return $this->role === UserRole::MIDDLE_LEVEL;
    }

    public function isTopLevel(): bool
    {
        return $this->role === UserRole::TOP_LEVEL;
    }

    /**
     * Check if this user can approve requests from target user based on hierarchy.
     */
    public function canApprove(User $targetUser): bool
    {
        if ($this->isTopLevel()) {
            return true;
        }

        if ($this->isMiddleLevel() && $targetUser->isFirstLevel()) {
            return true;
        }

        if ($this->isFirstLevel() && $targetUser->isStaf()) {
            return $targetUser->manager_id === $this->id;
        }

        return false;
    }

    /**
     * Determine if user has administrative privilege to manage MFA of users.
     * Top-Level Management and Middle Management are authorized.
     */
    public function canManageMfa(): bool
    {
        return $this->isTopLevel() || $this->isMiddleLevel();
    }
}

