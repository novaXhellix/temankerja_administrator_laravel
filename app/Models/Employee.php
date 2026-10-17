<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'work_shift_id',
        'uuid',
        'slug',
        'user_id',
        'foto',
        'region_id',
        'sub_region_id',
        'wilayah',
        'sub_wilayah',
        'nama_karyawan',
        'nik',
        'nip',
        'email_pribadi',
        'no_telepon',
        'alamat_ktp',
        'alamat_domisili',
        'status_kontrak',
        'status_karyawan',
        'jabatan',
        'lama_kontrak',
        'periode_kontrak_mulai',
        'periode_kontrak_selesai',
        'tgl_lahir',
        'jenis_kelamin',
        'tempat_lahir',
        'agama',
        'status_pernikahan',
        'pendidikan_terakhir',
        'mulai_bekerja',
        'bpjs_kes',
        'bpjs_tk',
        'npwp',
        'nama_bank',
        'no_rekening',
        'pemilik_rekening',
        'kontak_darurat_nama',
        'kontak_darurat_hubungan',
        'kontak_darurat_telepon',
        'tgl_resign',
        'alasan_resign',
    ];

    protected $appends = [
        'foto_url',
        'usia',
        'lama_bekerja',
    ];

    protected function casts(): array
    {
        return [
            'periode_kontrak_mulai' => 'date:Y-m-d',
            'periode_kontrak_selesai' => 'date:Y-m-d',
            'tgl_lahir' => 'date:Y-m-d',
            'mulai_bekerja' => 'date:Y-m-d',
            'tgl_resign' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
            if (empty($employee->slug)) {
                $baseSlug = Str::slug($employee->nama_karyawan);
                $employee->slug = "{$baseSlug}-" . strtolower(Str::random(6));
            }
        });
    }

    /**
     * URL Foto Profil Karyawan (Prioritas: Foto Upload S3 -> User Google Avatar -> Default Avatar).
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! empty($this->foto)) {
                    return str_starts_with($this->foto, 'http') ? $this->foto : Storage::disk(config('filesystems.default'))->url($this->foto);
                }

                if (! empty($this->user?->avatar)) {
                    return $this->user->avatar;
                }

                return asset('images/default-avatar.png');
            }
        );
    }

    /**
     * Calculated attribute: Usia (Age in years).
     */
    protected function usia(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tgl_lahir ? Carbon::parse($this->tgl_lahir)->age . ' Tahun' : '-'
        );
    }

    /**
     * Calculated attribute: Lama Bekerja (Tenure in years & months).
     */
    protected function lamaBekerja(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->mulai_bekerja) {
                    return '-';
                }

                $startDate = Carbon::parse($this->mulai_bekerja);
                $endDate = $this->tgl_resign ? Carbon::parse($this->tgl_resign) : Carbon::now();

                $diff = $startDate->diff($endDate);
                $years = $diff->y;
                $months = $diff->m;

                $result = [];
                if ($years > 0) {
                    $result[] = "{$years} Tahun";
                }
                if ($months > 0 || $years === 0) {
                    $result[] = "{$months} Bulan";
                }

                return implode(' ', $result);
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function subRegion(): BelongsTo
    {
        return $this->belongsTo(SubRegion::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
