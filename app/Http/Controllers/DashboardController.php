<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function mahasiswa(): View
    {
        return $this->tampilkan(Akun::ROLE_MAHASISWA);
    }

    public function keuangan(): View
    {
        return $this->tampilkan(Akun::ROLE_KEUANGAN);
    }

    public function verifikator(): View
    {
        return $this->tampilkan(Akun::ROLE_VERIFIKATOR);
    }

    private function tampilkan(string $roleDiizinkan): View
    {
        $akun = Auth::user();

        abort_unless($akun->role === $roleDiizinkan, 403);

       return view('dashboard', [
    'akun' => $akun,
    'role' => $roleDiizinkan,
    'judul' => 'Dashboard ' . $roleDiizinkan,
    'nama' => $akun->mahasiswa?->nama ?? $akun->username,
]);
    }
}