<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagihanUKT extends Model
{
    protected $table = 'tagihan_ukt';

    protected $primaryKey = 'id_tagihan';

    public $timestamps = false;

    protected $fillable = [
        'nim',
        'semester',
        'nominal',
        'batas_waktu',
        'status_bayar',
        'virtual_account',
        'midtrans_order_id',
        'va_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'batas_waktu' => 'date',
        ];
    }
            /**
     * PS-07: mengambil tagihan mahasiswa pada semester tertentu.
     * Mengembalikan null jika tagihan belum tersedia.
     */
    public static function getTagihanSemester(
        string $nim,
        string $semester
    ): ?self {
        return static::where('nim', $nim)
            ->where('semester', $semester)
            ->first();
    }


    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(
            Mahasiswa::class,
            'nim',
            'nim'
        );
    }

    public function transaksiRekonsiliasi(): HasMany
    {
        return $this->hasMany(
            TransaksiRekonsiliasi::class,
            'id_tagihan',
            'id_tagihan'
        );
    }
}