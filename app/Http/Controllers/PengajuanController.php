<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Mahasiswa;
use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use App\Services\PengajuanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UC-04 Ajukan Penyesuaian UKT | PS-03 | UI-04
 */
class PengajuanController extends Controller
{
    public function __construct(private PengajuanService $pengajuanService)
    {
    }

    // UI-04: menampilkan form pengajuan dan riwayat pengajuan sendiri.
    public function create(): View
    {
        $mahasiswa = $this->ambilMahasiswa();

        return view('pengajuan.form', [
            'mahasiswa' => $mahasiswa,
            'tagihan' => TagihanUKT::where('nim', $mahasiswa->nim)
                ->where('status_bayar', '!=', 'Lunas')
                ->orderBy('semester')
                ->get(),
            'riwayat' => PengajuanUKT::where('nim', $mahasiswa->nim)
                ->orderByDesc('id_pengajuan')
                ->get(),
        ]);
    }

    // PS-03: memproses form yang dikirim dari UI-04.
    public function store(Request $request): RedirectResponse
    {
        $mahasiswa = $this->ambilMahasiswa();

        $this->pengajuanService->submitPengajuan(
            $mahasiswa->nim,
            (string) $request->input('semester'),
            $request->input('alasan'),
            $request->file('dokumen')
        );

        return redirect()
            ->route('pengajuan.create')
            ->with('status', 'Pengajuan Sukses Terkirim');
    }

    // Hanya akun mahasiswa yang memiliki data mahasiswa yang boleh masuk.
    private function ambilMahasiswa(): Mahasiswa
    {
        $akun = Auth::user();

        abort_unless($akun?->role === Akun::ROLE_MAHASISWA, 403);

        $mahasiswa = $akun->mahasiswa;

        abort_if($mahasiswa === null, 403, 'Data mahasiswa tidak ditemukan.');

        return $mahasiswa;
    }
}
