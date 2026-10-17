<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReimbursementType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'nama_tipe',
        'kode_tipe',
        'maksimal_klaim',
        'memerlukan_struk',
        'status',
    ];

    protected $casts = [
        'maksimal_klaim' => 'float',
        'memerlukan_struk' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
