<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $appends = [
        'lampiran_url',
    ];

    protected $fillable = [
        'uuid',
        'employee_id',
        'leave_type_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'alasan',
        'lampiran_path',
        'status_persetujuan',
        'approved_by_user_id',
        'approved_at',
        'catatan_persetujuan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * URL Akses Lampiran PDF / Dokumen Cuti (Direct Object Storage S3)
     */
    protected function lampiranUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->lampiran_path
                ? (str_starts_with($this->lampiran_path, 'http') ? $this->lampiran_path : Storage::disk(config('filesystems.default'))->url($this->lampiran_path))
                : null
        );
    }
}
