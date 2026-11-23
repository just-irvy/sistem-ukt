<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanUKT extends Model
{
    public const STATUS_MENUNGGU = 'Menunggu Verifikasi';

    public const STATUS_DISETUJUI = 'Disetujui';

    public const STATUS_DITOLAK = 'Ditolak';

    protected $table = 'pengajuan_ukt';

    protected $primaryKey = 'id_pengajuan';

    public $timestamps = false;

    protected $fillable = [
        'nim',
        'semester',
        'alasan',
        'dokumen_pendukung',
        'status_verifikasi',
        'catatan_verifikator',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(
            Mahasiswa::class,
            'nim',
            'nim'
        );
    }

    /**
     * PS-03: menyimpan pengajuan baru dengan status awal
     * "Menunggu Verifikasi".
     */
    public static function savePengajuan(
        string $nim,
        string $semester,
        string $alasan,
        string $pathFile
    ): self {
        return static::create([
            'nim' => $nim,
            'semester' => $semester,
            'alasan' => $alasan,
            'dokumen_pendukung' => $pathFile,
            'status_verifikasi' => self::STATUS_MENUNGGU,
        ]);
    }

    /**
     * PS-04: menyimpan keputusan verifikator (status dan catatan).
     */
    public static function updateStatusVerifikasi(
        int $idPengajuan,
        string $status,
        ?string $catatan
    ): self {
        $pengajuan = static::findOrFail($idPengajuan);

        $pengajuan->update([
            'status_verifikasi' => $status,
            'catatan_verifikator' => $catatan,
        ]);

        return $pengajuan;
    }

    public function menunggu(): bool
    {
        return $this->status_verifikasi === self::STATUS_MENUNGGU;
    }
}
