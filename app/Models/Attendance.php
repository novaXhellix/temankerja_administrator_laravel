<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'employee_id',
        'company_id',
        'attendance_location_id',
        'work_shift_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'latitude_masuk',
        'longitude_masuk',
        'latitude_keluar',
        'longitude_keluar',
        'is_in_geofence_masuk',
        'is_in_geofence_keluar',
        'jarak_meter_masuk',
        'jarak_meter_keluar',
        'foto_masuk_path',
        'foto_keluar_path',
        'status_kehadiran',
        'menit_keterlambatan',
        'catatan',
    ];

    protected $casts = [
        'is_in_geofence_masuk' => 'boolean',
        'is_in_geofence_keluar' => 'boolean',
        'jarak_meter_masuk' => 'float',
        'jarak_meter_keluar' => 'float',
        'menit_keterlambatan' => 'integer',
        'tanggal' => 'date:Y-m-d',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'foto_masuk_url',
        'foto_keluar_url',
    ];

    public function getFotoMasukUrlAttribute(): ?string
    {
        return $this->foto_masuk_path
            ? (str_starts_with($this->foto_masuk_path, 'http') ? $this->foto_masuk_path : Storage::disk(config('filesystems.default'))->url($this->foto_masuk_path))
            : null;
    }

    public function getFotoKeluarUrlAttribute(): ?string
    {
        return $this->foto_keluar_path
            ? (str_starts_with($this->foto_keluar_path, 'http') ? $this->foto_keluar_path : Storage::disk(config('filesystems.default'))->url($this->foto_keluar_path))
            : null;
    }

    public function getStatusLabelAttribute(): string
    {
        $enum = \App\Enums\AttendanceStatus::tryFrom($this->status_kehadiran);
        return $enum ? $enum->label() : ($this->status_kehadiran ?: 'Hadir');
    }

    public function getStatusBadgeAttribute(): string
    {
        $enum = \App\Enums\AttendanceStatus::tryFrom($this->status_kehadiran);
        return $enum ? $enum->badgeClasses() : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    }

    protected static function booted(): void
    {
        static::creating(function (Attendance $attendance) {
            if (empty($attendance->uuid)) {
                $attendance->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'work_shift_id');
    }
}

