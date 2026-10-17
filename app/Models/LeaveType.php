<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'nama_tipe',
        'kategori',
        'kuota_hari_pertahun',
        'memerlukan_lampiran',
        'potong_cuti_tahunan',
        'status',
    ];

    protected $casts = [
        'memerlukan_lampiran' => 'boolean',
        'potong_cuti_tahunan' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
