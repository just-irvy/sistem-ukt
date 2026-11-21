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

    /**
     * UC-03 langkah 2: daftar periode semester untuk dropdown.
     * Menggabungkan semester yang sudah punya tagihan dengan beberapa
     * periode terakhir, sehingga mahasiswa tetap bisa memilih periode
     * yang belum ditetapkan dan melihat pesan "Tagihan belum tersedia".
     * Urutan: terbaru lebih dulu.
     */
    public static function getDaftarSemester(string $nim): array
    {
        $tahun = (int) now()->format('Y');
        $tahunAjaran = (int) now()->format('n') >= 8 ? $tahun : $tahun - 1;

        $daftar = [];

        // Empat tahun ajaran terakhir, dari yang terbaru.
        for ($t = $tahunAjaran; $t > $tahunAjaran - 4; $t--) {
            $daftar[] = 'Genap '.$t.'/'.($t + 1);
            $daftar[] = 'Ganjil '.$t.'/'.($t + 1);
        }

        $adaTagihan = static::where('nim', $nim)->pluck('semester')->all();

        $daftar = array_values(array_unique(array_merge($daftar, $adaTagihan)));

        // Ganjil/Genap dengan tahun yang sama: Genap tampil di atas Ganjil.
        usort($daftar, function (string $a, string $b): int {
            return strcmp(static::kunciUrutSemester($b), static::kunciUrutSemester($a));
        });

        return $daftar;
    }

    // "Ganjil 2026/2027" -> "2026-1", "Genap 2026/2027" -> "2026-2".
    private static function kunciUrutSemester(string $semester): string
    {
        preg_match('/(\d{4})/', $semester, $cocok);
        $tahun = $cocok[1] ?? '0000';
        $urutan = str_starts_with($semester, 'Genap') ? '2' : '1';

        return $tahun.'-'.$urutan;
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