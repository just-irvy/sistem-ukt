<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * UC-04 Ajukan Penyesuaian UKT | PS-03 submitPengajuan()
 */
class PengajuanService
{
    // Batas ukuran dokumen pendukung (KB) dan format yang diizinkan.
    public const MAKS_UKURAN_KB = 2048;

    public const FORMAT_DIIZINKAN = ['pdf', 'jpg', 'jpeg'];

    public function submitPengajuan(
        string $nim,
        string $semester,
        ?string $alasan,
        ?UploadedFile $dokumen
    ): PengajuanUKT {
        // 1. Form dan dokumen wajib valid (Alternative Flow 1 dan 2 UC-04).
        Validator::make(
            [
                'semester' => $semester,
                'alasan' => $alasan,
                'dokumen' => $dokumen,
            ],
            [
                'semester' => ['required', 'string', 'max:20'],
                'alasan' => ['required', 'string', 'max:2000'],
                'dokumen' => [
                    'required',
                    'file',
                    'mimes:' . implode(',', self::FORMAT_DIIZINKAN),
                    'max:' . self::MAKS_UKURAN_KB,
                ],
            ],
            [
                'semester.required' => 'Pilih semester yang diajukan.',
                'alasan.required' => 'Harap lengkapi alasan pengajuan.',
                'dokumen.required' => 'Harap lampirkan dokumen pendukung.',
                'dokumen.mimes' => 'Dokumen harus berformat PDF atau JPG.',
                'dokumen.max' => 'Ukuran dokumen maksimal 2 MB.',
            ]
        )->validate();

        // 2. Mahasiswa dan tagihan semester tersebut harus ada.
        if (!Mahasiswa::checkNIM($nim)) {
            throw ValidationException::withMessages([
                'nim' => 'Data mahasiswa tidak ditemukan.',
            ]);
        }

        $tagihan = TagihanUKT::getTagihanSemester($nim, $semester);

        if ($tagihan === null) {
            throw ValidationException::withMessages([
                'semester' => 'Tagihan untuk semester tersebut belum tersedia.',
            ]);
        }

        if ($tagihan->status_bayar === 'Lunas') {
            throw ValidationException::withMessages([
                'semester' => 'Tagihan semester tersebut sudah lunas, penyesuaian tidak dapat diajukan.',
            ]);
        }

        // 3. Satu semester hanya boleh memiliki satu pengajuan yang
        //    masih menunggu verifikasi.
        $sudahAdaPengajuan = PengajuanUKT::where('nim', $nim)
            ->where('semester', $semester)
            ->where('status_verifikasi', PengajuanUKT::STATUS_MENUNGGU)
            ->exists();

        if ($sudahAdaPengajuan) {
            throw ValidationException::withMessages([
                'semester' => 'Pengajuan untuk semester tersebut masih menunggu verifikasi.',
            ]);
        }

        // 4. Simpan file lalu data pengajuan. Jika penyimpanan data
        //    gagal, file dihapus agar tidak ada file yatim.
        $pathFile = $dokumen->store('pengajuan-ukt', 'local');

        try {
            return DB::transaction(
                fn () => PengajuanUKT::savePengajuan(
                    $nim,
                    $semester,
                    (string) $alasan,
                    $pathFile
                )
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($pathFile);

            throw $exception;
        }
    }
}
