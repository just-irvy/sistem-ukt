<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verifikator extends Model
{
    protected $table = 'verifikator';

    protected $primaryKey = 'nip';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'nip',
        'nama',
        'id_akun',
    ];

    public function akun(): BelongsTo
    {
        return $this->belongsTo(
            Akun::class,
            'id_akun',
            'id_akun'
        );
    }
}
