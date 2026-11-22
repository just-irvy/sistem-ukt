<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use App\Services\VerifikasiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * UC-05 Verifikasi Pengajuan UKT | PS-04 | UI-05
 */
class VerifikasiController extends Controller
{
    public function __construct(private VerifikasiService $verifikasiService)
    {
    }

    // UI-05: daftar pengajuan masuk (yang menunggu ditampilkan lebih dulu).
    public function index(): View
    {
        $this->pastikanVerifikator();

        $daftar = PengajuanUKT::with('mahasiswa')
            ->orderByRaw('CASE WHEN status_verifikasi = ? THEN 0 ELSE 1 END', [
                PengajuanUKT::STATUS_MENUNGGU,
            ])
            ->orderByDesc('id_pengajuan')
            ->get();

        return view('verifikasi.index', [
            'daftar' => $daftar,
            'jumlahMenunggu' => $daftar->where('status_verifikasi', PengajuanUKT::STATUS_MENUNGGU)->count(),
            'jumlahDisetujui' => $daftar->where('status_verifikasi', PengajuanUKT::STATUS_DISETUJUI)->count(),
            'jumlahDitolak' => $daftar->where('status_verifikasi', PengajuanUKT::STATUS_DITOLAK)->count(),
        ]);
    }

    // UI-05: detail alasan, dokumen pendukung, dan form keputusan.
    public function show(int $idPengajuan): View
    {
        $this->pastikanVerifikator();

        $pengajuan = PengajuanUKT::with('mahasiswa')->findOrFail($idPengajuan);

        return view('verifikasi.detail', [
            'pengajuan' => $pengajuan,
            'tagihan' => TagihanUKT::getTagihanSemester(
                $pengajuan->nim,
                $pengajuan->semester
            ),
        ]);
    }

    // Menampilkan dokumen pendukung (hanya untuk verifikator).
    public function dokumen(int $idPengajuan): StreamedResponse
    {
        $this->pastikanVerifikator();

        $pengajuan = PengajuanUKT::findOrFail($idPengajuan);

        abort_unless(
            Storage::disk('local')->exists($pengajuan->dokumen_pendukung),
            404,
            'Dokumen tidak ditemukan.'
        );

        return Storage::disk('local')->response($pengajuan->dokumen_pendukung);
    }

    // PS-04: memproses keputusan dari UI-05.
    public function proses(Request $request, int $idPengajuan): RedirectResponse
    {
        $this->pastikanVerifikator();

        $pengajuan = PengajuanUKT::findOrFail($idPengajuan);

        $tagihan = TagihanUKT::getTagihanSemester(
            $pengajuan->nim,
            $pengajuan->semester
        );

        $this->verifikasiService->prosesVerifikasi(
            $idPengajuan,
            (string) $request->input('keputusan'),
            $request->input('catatan'),
            $tagihan?->id_tagihan,
            $request->input('nominal_baru')
        );

        return redirect()
            ->route('verifikasi.index')
            ->with('status', 'Verifikasi Selesai');
    }

    private function pastikanVerifikator(): void
    {
        abort_unless(Auth::user()?->role === Akun::ROLE_VERIFIKATOR, 403);
    }
}
