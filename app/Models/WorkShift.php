<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'nama_shift',
        'kode_shift',
        'jam_masuk',
        'jam_keluar',
        'schedule_days',
        'work_days',
        'toleransi_keterlambatan_menit',
        'is_night_shift',
        'status',
        'catatan',
    ];

    protected $casts = [
        'is_night_shift' => 'boolean',
        'toleransi_keterlambatan_menit' => 'integer',
        'schedule_days' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
