<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'slug',
        'nama_customer',
        'kode_customer',
        'email',
        'telepon',
        'alamat',
        'pic_nama',
        'pic_telepon',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
            if (empty($customer->slug)) {
                $baseSlug = Str::slug($customer->nama_customer);
                $customer->slug = "{$baseSlug}-" . strtolower(Str::random(5));
            }
        });
    }

    public function routes(): HasMany
    {
        return $this->hasMany(CustomerRoute::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
