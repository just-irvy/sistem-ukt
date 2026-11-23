<?php

namespace App\Services;

use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * UC-05 Verifikasi Pengajuan UKT | PS-04 prosesVerifikasi()
 */
class VerifikasiService
{
    public function prosesVerifikasi(
        int $idPengajuan,
        string $statusKeputusan,
        ?string $catatan,
        ?int $idTagihan,
        string|int|float|null $nominalBaru
    ): PengajuanUKT {
        // 1. Keputusan harus Disetujui atau Ditolak.
        if (!in_array($statusKeputusan, [
            PengajuanUKT::STATUS_DISETUJUI,
            PengajuanUKT::STATUS_DITOLAK,
        ], true)) {
            throw ValidationException::withMessages([
                'keputusan' => 'Keputusan verifikasi tidak valid.',
            ]);
        }

        $catatan = $catatan === null ? null : trim($catatan);

        // 2. Penolakan wajib disertai catatan (Alternative Flow UC-05).
        if ($statusKeputusan === PengajuanUKT::STATUS_DITOLAK && blank($catatan)) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan wajib diisi saat menolak pengajuan.',
            ]);
        }

        return DB::transaction(function () use (
            $idPengajuan,
            $statusKeputusan,
            $catatan,
            $idTagihan,
            $nominalBaru
        ) {
            // 3. Kunci baris pengajuan agar tidak diputuskan dua kali.
            $pengajuan = PengajuanUKT::lockForUpdate()->find($idPengajuan);

            if ($pengajuan === null) {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Data pengajuan tidak ditemukan.',
                ]);
            }

            if (!$pengajuan->menunggu()) {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Pengajuan ini sudah diputuskan sebelumnya.',
                ]);
            }

            // 4. Persetujuan: nominal baru dan tagihan harus valid.
            if ($statusKeputusan === PengajuanUKT::STATUS_DISETUJUI) {
                if (
                    !is_numeric($nominalBaru)
                    || (float) $nominalBaru <= 0
                    || (float) $nominalBaru >= 10000000000
                ) {
                    throw ValidationException::withMessages([
                        'nominal_baru' => 'Nominal baru harus berupa angka lebih besar dari nol.',
                    ]);
                }

                $tagihan = $idTagihan === null
                    ? null
                    : TagihanUKT::find($idTagihan);

                if (
                    $tagihan === null
                    || $tagihan->nim !== $pengajuan->nim
                    || $tagihan->semester !== $pengajuan->semester
                ) {
                    throw ValidationException::withMessages([
                        'tagihan' => 'Tagihan untuk pengajuan ini tidak ditemukan.',
                    ]);
                }

                if ($tagihan->status_bayar === 'Lunas') {
                    throw ValidationException::withMessages([
                        'tagihan' => 'Tagihan sudah lunas, nominal tidak dapat diubah.',
                    ]);
                }
            }

            // 5. Simpan hasil verifikasi, lalu perbarui nominal bila disetujui.
            PengajuanUKT::updateStatusVerifikasi(
                $idPengajuan,
                $statusKeputusan,
                $catatan
            );

            if ($statusKeputusan === PengajuanUKT::STATUS_DISETUJUI) {
                TagihanUKT::updateNominal($idTagihan, $nominalBaru);
            }

            return $pengajuan->fresh();
        });
    }
}
