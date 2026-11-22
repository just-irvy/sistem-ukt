<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';

    protected $primaryKey = 'nim';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'nim',
        'nama',
        'nomor_telepon',
        'fakultas',
        'prodi',
        'id_akun',
    ];

    public function tagihanUkt(): HasMany
    {
        return $this->hasMany(
            TagihanUKT::class,
            'nim',
            'nim'
        );
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(
            Akun::class,
            'id_akun',
            'id_akun'
        );
    }

    /**
     * PS-01: true jika NIM sudah terdaftar.
     */
    public static function checkNIM(string $nim): bool
    {
        return static::where('nim', $nim)->exists();
    }
}
