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
        'no_telp',
        'fakultas',
        'prodi',
        'id_akun',
    ];

    /**
     * PS-01: Mengecek apakah NIM sudah terdaftar.
     */
    public static function checkNIM(string $nim): bool
    {
        return static::where('nim', $nim)->exists();
    }

    /**
     * Relasi mahasiswa dengan tagihan UKT.
     */
    public function tagihanUkt(): HasMany
    {
        return $this->hasMany(
            TagihanUKT::class,
            'nim',
            'nim'
        );
    }

    /**
     * Relasi mahasiswa dengan pengajuan UKT.
     * Digunakan oleh UC-04 dan UC-05.
     */
    public function pengajuanUkt(): HasMany
    {
        return $this->hasMany(
            PengajuanUKT::class,
            'nim',
            'nim'
        );
    }

    /**
     * Relasi mahasiswa dengan akun login.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(
            Akun::class,
            'id_akun',
            'id_akun'
        );
    }
}
