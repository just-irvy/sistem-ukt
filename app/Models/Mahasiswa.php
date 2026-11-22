<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

        public static function checkNIM(string $nim): bool
    {
        return static::where('nim', $nim)->exists();
    }

    public function tagihanUkt(): HasMany
    {
        return $this->hasMany(
            TagihanUKT::class,
            'nim',
            'nim'
        );
    }
}