<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'company_id',
        'uuid',
        'slug',
        'nama_sub_wilayah',
        'kode_sub_wilayah',
        'tipe',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (SubRegion $subRegion) {
            if (empty($subRegion->uuid)) {
                $subRegion->uuid = (string) Str::uuid();
            }
            if (empty($subRegion->slug)) {
                $baseSlug = Str::slug($subRegion->nama_sub_wilayah);
                $subRegion->slug = "{$baseSlug}-" . strtolower(Str::random(5));
            }
        });
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'sub_region_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'sub_region_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
