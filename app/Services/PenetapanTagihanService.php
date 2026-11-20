<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenetapanTagihanService
{
    /**
     * Menetapkan tagihan UKT mahasiswa berdasarkan semester.
     *
     * @param array{
     *     nim: string,
     *     semester: string,
     *     nominal: numeric,
     *     batas_waktu: string
     * } $data
     */
    public function savePenetapanTagihan(array $data): TagihanUKT
    {
        // 1. Memastikan mahasiswa tersedia.
        $mahasiswa = Mahasiswa::where('nim', $data['nim'])->first();

        if (!$mahasiswa) {
            throw ValidationException::withMessages([
                'nim' => 'Data mahasiswa tidak ditemukan.',
            ]);
        }

        // 2. Validasi nominal UKT.
        if (
            !is_numeric($data['nominal']) ||
            (float) $data['nominal'] <= 0
        ) {
            throw ValidationException::withMessages([
                'nominal' => 'Nominal UKT harus lebih besar dari nol.',
            ]);
        }

        // 3. Memeriksa apakah tagihan semester sudah tersedia.
        $tagihanExist = TagihanUKT::where('nim', $data['nim'])
            ->where('semester', $data['semester'])
            ->exists();

        if ($tagihanExist) {
            throw ValidationException::withMessages([
                'semester' => 'Tagihan mahasiswa untuk semester tersebut sudah tersedia.',
            ]);
        }

        // 4. Simpan tagihan menggunakan database transaction.
        return DB::transaction(function () use ($data) {
            $tagihan = TagihanUKT::create([
                'nim' => $data['nim'],
                'semester' => $data['semester'],
                'nominal' => $data['nominal'],
                'batas_waktu' => $data['batas_waktu'],
                'status_bayar' => 'Belum Dibayar',
                'virtual_account' => null,
            ]);

            // 5. Tagihan langsung tersedia untuk ditampilkan
            //    kepada mahasiswa karena belum ada kolom status_rilis.

            return $tagihan;
        });
    }
}
