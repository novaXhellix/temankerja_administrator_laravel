<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'uuid',
        'slug',
        'nama_wilayah',
        'kode_wilayah',
        'alamat_kantor',
        'telepon',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Region $region) {
            if (empty($region->uuid)) {
                $region->uuid = (string) Str::uuid();
            }
            if (empty($region->slug)) {
                $baseSlug = Str::slug($region->nama_wilayah);
                $region->slug = "{$baseSlug}-" . strtolower(Str::random(5));
            }
        });
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function subRegions(): HasMany
    {
        return $this->hasMany(SubRegion::class);
    }

    public function operatingCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'region_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
