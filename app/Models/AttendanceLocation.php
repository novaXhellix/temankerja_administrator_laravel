<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'region_id',
        'nama_lokasi',
        'latitude',
        'longitude',
        'radius_meter',
        'alamat_lengkap',
        'status',
        'check_type',
        'polygon_coordinates',
    ];

    protected $casts = [
        'polygon_coordinates' => 'array',
    ];


    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
