<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'uuid',
        'slug',
        'nama_departemen',
        'kode_departemen',
        'deskripsi',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Department $department) {
            if (empty($department->uuid)) {
                $department->uuid = (string) Str::uuid();
            }
            if (empty($department->slug)) {
                $baseSlug = Str::slug($department->nama_departemen);
                $department->slug = "{$baseSlug}-" . strtolower(Str::random(5));
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
