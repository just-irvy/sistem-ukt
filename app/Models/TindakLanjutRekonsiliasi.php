<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakLanjutRekonsiliasi extends Model
{
    protected $table = 'tindak_lanjut_rekonsiliasi';
    protected $primaryKey = 'id_tindak_lanjut';

    protected $fillable = [
        'id_transaksi', 'id_akun', 'status_tindak_lanjut', 'catatan',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiRekonsiliasi::class, 'id_transaksi', 'id_transaksi');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }
}
