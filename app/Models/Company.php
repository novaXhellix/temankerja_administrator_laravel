<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'slug',
        'nama_perusahaan',
        'kode_perusahaan',
        'logo_path',
        'primary_color',
        'secondary_color',
        'email',
        'telepon',
        'alamat',
        'website',
        'status',
        'cutoff_start_day',
        'region_id',
        'sub_region_id',
        'wilayah_operasional',
        'sub_wilayah',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->uuid)) {
                $company->uuid = (string) Str::uuid();
            }
            if (empty($company->slug)) {
                $baseSlug = Str::slug($company->nama_perusahaan);
                $company->slug = "{$baseSlug}-" . strtolower(Str::random(6));
            }
        });
    }

    /**
     * Accessor for full logo URL.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path
                ? (str_starts_with($this->logo_path, 'http') ? $this->logo_path : Storage::disk(config('filesystems.default'))->url($this->logo_path))
                : null
        );
    }

    public function region(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function subRegion(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubRegion::class, 'sub_region_id');
    }

    public function regions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function departments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
