<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman tujuan setelah login (UC-02). Isi dashboard tiap role
 * dilengkapi oleh anggota yang mengerjakan use case terkait.
 */
class DashboardController extends Controller
{
    public function mahasiswa(): View
    {
        return $this->tampilkan(Akun::ROLE_MAHASISWA, 'Dashboard Mahasiswa');
    }

    public function keuangan(): View
    {
        return $this->tampilkan(Akun::ROLE_KEUANGAN, 'Dashboard Bagian Keuangan');
    }

    public function verifikator(): View
    {
        return $this->tampilkan(Akun::ROLE_VERIFIKATOR, 'Dashboard Verifikator');
    }

    // Menolak akses jika role akun tidak sesuai dengan halaman.
    private function tampilkan(string $role, string $judul): View
    {
        $akun = Auth::user();

        abort_unless($akun->role === $role, 403);

        return view('dashboard', [
            'judul' => $judul,
            'nama' => $akun->mahasiswa?->nama ?? $akun->username,
            'role' => $akun->role,
        ]);
    }
}
