<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiRekonsiliasi extends Model
{
    protected $table = 'transaksi_rekonsiliasi';

    protected $primaryKey = 'id_transaksi';

    public $timestamps = false;

    protected $fillable = [
        'id_tagihan',
        'virtual_account',
        'referensi_bank',
        'nominal_bayar',
        'waktu_transaksi',
        'status_flag',
    ];

    protected function casts(): array
    {
        return [
            'nominal_bayar' => 'decimal:2',
            'waktu_transaksi' => 'datetime',
        ];
    }

    public function tagihanUkt(): BelongsTo
    {
        return $this->belongsTo(
            TagihanUKT::class,
            'id_tagihan',
            'id_tagihan'
        );
    }
}