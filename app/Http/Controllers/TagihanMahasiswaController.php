<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use App\Services\MidtransService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * UC-07: pembayaran VA BCA Sandbox untuk mahasiswa yang telah login.
 * Identitas dan kepemilikan tagihan selalu berasal dari sesi autentikasi.
 */
class TagihanMahasiswaController extends Controller
{
    public function index(Request $request): View
    {
        $mahasiswa = $this->mahasiswaLogin($request);

        $validated = $request->validate([
            'semester' => ['nullable', 'string', 'max:20'],
        ]);

        $semesterOptions = TagihanUKT::query()
            ->where('nim', $mahasiswa->nim)
            ->select('semester')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester');

        $semester = $validated['semester'] ?? $semesterOptions->first();

        $tagihan = $semester === null
            ? null
            : TagihanUKT::query()
                ->where('nim', $mahasiswa->nim)
                ->where('semester', $semester)
                ->first();

        return view('mahasiswa.tagihan', [
            'nim' => $mahasiswa->nim,
            'mahasiswa' => $mahasiswa,
            'semester' => $semester,
            'semesterOptions' => $semesterOptions,
            'tagihan' => $tagihan,
        ]);
    }

    public function buatVirtualAccount(
        Request $request,
        int $idTagihan,
        MidtransService $midtransService
    ): RedirectResponse {
        $mahasiswa = $this->mahasiswaLogin($request);

        // ID dari URL tidak cukup: tagihan harus milik akun yang sedang login.
        $tagihan = TagihanUKT::query()
            ->whereKey($idTagihan)
            ->where('nim', $mahasiswa->nim)
            ->firstOrFail();

        $kembali = redirect()->route('mahasiswa.tagihan.index', [
            'semester' => $tagihan->semester,
        ]);

        if ($tagihan->status_bayar !== 'Belum Dibayar') {
            return $kembali->with(
                'va_error',
                'Tagihan ini tidak dapat dibayarkan karena statusnya bukan Belum Dibayar.'
            );
        }

        // Jangan menerbitkan ulang VA lama/kedaluwarsa tanpa verifikasi status
        // transaksi Midtrans. Rekonsiliasi tetap menjadi tanggung jawab UC-08.
        if (!empty($tagihan->virtual_account)) {
            return $kembali->with(
                'va_info',
                'Nomor Virtual Account untuk tagihan ini sudah tersimpan.'
            );
        }

        // Implementasi akademik ini hanya mengizinkan transaksi Sandbox.
        if ((bool) config('midtrans.is_production', false)) {
            return $kembali->with(
                'va_error',
                'Pembuatan Virtual Account hanya tersedia pada Midtrans Sandbox.'
            );
        }

        try {
            $hasil = $midtransService->buatVirtualAccount((int) $tagihan->id_tagihan);

            return $kembali
                ->with('va_success', 'Virtual Account BCA Sandbox berhasil diminta dan disimpan.')
                ->with('va_expiry', $hasil['expiry_time'] ?? null);
        } catch (Throwable $exception) {
            report($exception);

            return $kembali->with(
                'va_error',
                'Gagal memuat Virtual Account. Periksa konfigurasi Midtrans Sandbox dan log aplikasi, lalu coba lagi.'
            );
        }
    }

    private function mahasiswaLogin(Request $request): Mahasiswa
    {
        $akun = $request->user();

        abort_unless(
            $akun instanceof Akun && $akun->role === Akun::ROLE_MAHASISWA,
            403,
            'Halaman pembayaran hanya untuk Mahasiswa.'
        );

        $mahasiswa = $akun->mahasiswa;

        abort_unless(
            $mahasiswa instanceof Mahasiswa,
            403,
            'Profil mahasiswa untuk akun ini tidak ditemukan.'
        );

        return $mahasiswa;
    }
}
