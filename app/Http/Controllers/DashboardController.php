<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard pengguna berdasarkan role.
 * Mendukung Mahasiswa, Bagian Keuangan, dan Verifikator.
 */
class DashboardController extends Controller
{
    /**
     * Dashboard Mahasiswa.
     */
    public function mahasiswa(): View
    {
        return $this->tampilkan(
            Akun::ROLE_MAHASISWA,
            'Dashboard Mahasiswa'
        );
    }

    /**
     * Dashboard Bagian Keuangan.
     */
    public function keuangan(): View
    {
        return $this->tampilkan(
            Akun::ROLE_KEUANGAN,
            'Dashboard Bagian Keuangan'
        );
    }

    /**
     * Dashboard Verifikator.
     */
    public function verifikator(): View
    {
        return $this->tampilkan(
            Akun::ROLE_VERIFIKATOR,
            'Dashboard Verifikator'
        );
    }

    /**
     * Menampilkan dashboard sesuai role pengguna.
     * Akses ditolak jika role tidak sesuai.
     */
    private function tampilkan(
        string $roleDiizinkan,
        string $judul
    ): View {
        $akun = Auth::user();

        abort_unless(
            $akun?->role === $roleDiizinkan,
            403
        );

        return view('dashboard', [
            'akun' => $akun,
            'role' => $roleDiizinkan,
            'judul' => $judul,
            'nama' => $akun->mahasiswa?->nama
                ?? $akun->username,
        ]);
    }
}
