<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeContract extends Model
{
    use HasFactory;

    protected $appends = [
        'dokumen_kontrak_url',
    ];

    protected $fillable = [
        'uuid',
        'employee_id',
        'company_id',
        'nomor_kontrak',
        'jenis_kontrak',
        'tanggal_mulai',
        'tanggal_selesai',
        'durasi_bulan',
        'jabatan',
        'divisi',
        'gaji_pokok',
        'catatan_khusus',
        'dokumen_kontrak_path',
        'status_kontrak',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'gaji_pokok' => 'float',
        'durasi_bulan' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (EmployeeContract $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
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

    /**
     * URL Akses Dokumen Kontrak PDF (Direct Object Storage S3)
     */
    protected function dokumenKontrakUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dokumen_kontrak_path
                ? (str_starts_with($this->dokumen_kontrak_path, 'http') ? $this->dokumen_kontrak_path : Storage::disk(config('filesystems.default'))->url($this->dokumen_kontrak_path))
                : null
        );
    }
}
