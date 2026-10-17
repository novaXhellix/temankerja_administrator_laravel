<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Reimbursement extends Model
{
    use HasFactory;

    protected $appends = [
        'bukti_nota_url',
    ];

    protected $fillable = [
        'uuid',
        'nomor_klaim',
        'employee_id',
        'company_id',
        'reimbursement_type_id',
        'tanggal_klaim',
        'nominal',
        'keterangan',
        'bukti_nota_path',
        'status',
        'approved_by_user_id',
        'approved_at',
        'catatan_approval',
        'tanggal_pencairan',
        'metode_pembayaran',
    ];

    protected $casts = [
        'tanggal_klaim' => 'date:Y-m-d',
        'tanggal_pencairan' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'nominal' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reimbursement $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->nomor_klaim)) {
                $dateCode = date('Ymd');
                $random = strtoupper(Str::random(4));
                $model->nomor_klaim = "CLM-{$dateCode}-{$random}";
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

    public function reimbursementType(): BelongsTo
    {
        return $this->belongsTo(ReimbursementType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * URL Akses Bukti Nota / Foto / PDF Klaim Reimbursement (Direct Object Storage S3)
     */
    protected function buktiNotaUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bukti_nota_path
                ? (str_starts_with($this->bukti_nota_path, 'http') ? $this->bukti_nota_path : Storage::disk(config('filesystems.default'))->url($this->bukti_nota_path))
                : null
        );
    }
}
