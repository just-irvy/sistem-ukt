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
    ];

    public function tagihanUkt(): HasMany
    {
        return $this->hasMany(
            TagihanUKT::class,
            'nim',
            'nim'
        );
    }
}