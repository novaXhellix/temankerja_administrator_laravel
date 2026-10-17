<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_id',
        'uuid',
        'slug',
        'nama_rute',
        'tempat_muat',
        'tempat_bongkar',
        'kargo',
        'km',
        'tarif_standar',
        'catatan',
        'status',
    ];

    protected $casts = [
        'km' => 'float',
        'tarif_standar' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerRoute $route) {
            if (empty($route->uuid)) {
                $route->uuid = (string) Str::uuid();
            }
            if (empty($route->nama_rute)) {
                $route->nama_rute = "{$route->tempat_muat} → {$route->tempat_bongkar}";
            }
            if (empty($route->slug)) {
                $baseSlug = Str::slug("{$route->tempat_muat}-ke-{$route->tempat_bongkar}");
                $route->slug = "{$baseSlug}-" . strtolower(Str::random(5));
            }
        });

        static::updating(function (CustomerRoute $route) {
            if (empty($route->nama_rute)) {
                $route->nama_rute = "{$route->tempat_muat} → {$route->tempat_bongkar}";
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
