<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Throwable;
use Illuminate\View\View;

class TagihanMahasiswaController extends Controller
{
    /**
     * Pratinjau UI-03 untuk pengujian lokal saja.
     * Identitas mahasiswa belum memakai autentikasi; jangan gunakan pada produksi.
     */
    public function index(Request $request): View
    {
        $this->pastikanPratinjauLokal($request);

        $validated = $request->validate([
            'nim' => ['nullable', 'string', 'regex:/^[0-9]{1,15}$/'],
            'semester' => ['nullable', 'string', 'max:20'],
        ]);

        $nim = $validated['nim'] ?? null;
        $mahasiswa = $nim ? Mahasiswa::find($nim) : null;

        // Menampilkan periode yang benar-benar ada di data tagihan.
        $semesterOptions = TagihanUKT::query()
            ->select('semester')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester');

        $semester = $validated['semester'] ?? $semesterOptions->first();
        $tagihan = null;

        if ($mahasiswa !== null && $semester !== null) {
            // NIM tetap wajib menjadi filter saat mengambil tagihan.
            $tagihan = TagihanUKT::query()
                ->where('nim', $mahasiswa->nim)
                ->where('semester', $semester)
                ->first();
        }

        return view('mahasiswa.tagihan', [
            'nim' => $nim,
            'mahasiswa' => $mahasiswa,
            'semester' => $semester,
            'semesterOptions' => $semesterOptions,
            'tagihan' => $tagihan,
        ]);
    }

    /**
     * Meminta VA BCA Sandbox melalui service yang sudah ada.
     * Endpoint ini khusus pengujian pada komputer lokal, BUKAN autentikasi mahasiswa.
     */
    public function buatVirtualAccount(
        Request $request,
        int $idTagihan,
        MidtransService $midtransService
    ): RedirectResponse {
        $this->pastikanPratinjauLokal($request);

        $validated = $request->validate([
            'nim' => ['required', 'string', 'regex:/^[0-9]{1,15}$/'],
            'semester' => ['required', 'string', 'max:20'],
        ]);

        // Wajib cocok dengan NIM dan semester pada halaman pratinjau.
        // Ini hanya pemeriksaan konsistensi lokal, bukan otorisasi produksi.
        $tagihan = TagihanUKT::query()
            ->whereKey($idTagihan)
            ->where('nim', $validated['nim'])
            ->where('semester', $validated['semester'])
            ->firstOrFail();

        $kembali = redirect()->route('mahasiswa.tagihan.index', [
            'nim' => $validated['nim'],
            'semester' => $validated['semester'],
        ]);

        if ($tagihan->status_bayar !== 'Belum Dibayar') {
            return $kembali->with('va_error', 'Tagihan ini tidak dapat dibayarkan karena statusnya bukan Belum Dibayar.');
        }

        if (!empty($tagihan->virtual_account)) {
            return $kembali->with('va_info', 'Nomor Virtual Account untuk tagihan ini sudah tersimpan.');
        }

        try {
            // Tidak mengubah MidtransService ataupun proses UC-08.
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

    /** Blokir akses pratinjau dari luar komputer pengembang. */
    private function pastikanPratinjauLokal(Request $request): void
    {
        abort_unless(
            app()->environment('local')
                && in_array($request->ip(), ['127.0.0.1', '::1'], true),
            403
        );
    }
}
