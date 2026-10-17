<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'bulan',
        'tahun',
        'total_hari_kerja',
        'total_hadir',
        'total_terlambat',
        'total_izin',
        'total_cuti',
        'total_sakit',
        'total_alpa',
        'total_menit_keterlambatan',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
