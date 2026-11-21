<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Mahasiswa;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * UC-01 Registrasi Akun | SD-01 | PS-01 | UI-01
 */
class RegistrasiController extends Controller
{
    private const FIELD_WAJIB = [
        'nama',
        'nim',
        'no_telp',
        'prodi',
        'password',
        'fakultas',
    ];

    // Menampilkan Form Registrasi UI (UI-01).
    public function tampilkanFormRegistrasi(): View
    {
        return view('auth.registrasi');
    }

    // PS-01: memproses data dari Form Registrasi UI.
    public function submitRegistrasi(Request $request): RedirectResponse
    {
        $dataRegistrasi = $request->only(self::FIELD_WAJIB);

        // 1. Validasi kelengkapan data.
        if (!$this->validasiKelengkapan($dataRegistrasi)) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['registrasi' => 'Data tidak lengkap']);
        }

        // 2. Validasi format dan panjang data sesuai DDL.
        $request->validate([
            'nama' => ['string', 'max:100'],
            'nim' => ['string', 'max:15', 'regex:/^[0-9]+$/'],
            'no_telp' => ['string', 'max:15', 'regex:/^[0-9+]+$/'],
            'fakultas' => ['string', 'max:50'],
            'prodi' => ['string', 'max:50'],
            'password' => ['string', 'min:8', 'max:255'],
        ], [
            'nim.regex' => 'NIM hanya boleh berisi angka.',
            'no_telp.regex' => 'Nomor telepon hanya boleh berisi angka (boleh diawali +).',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        // 3. Periksa apakah NIM sudah terdaftar.
        if (Mahasiswa::checkNIM($dataRegistrasi['nim'])) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['registrasi' => 'NIM sudah terdaftar']);
        }

        // 4. Buat akun beserta data mahasiswa.
        try {
            Akun::createAkun($dataRegistrasi);
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'registrasi' => 'Registrasi gagal, silakan coba lagi.',
                ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Registrasi Berhasil');
    }

    // Memastikan seluruh field wajib terisi.
    private function validasiKelengkapan(array $data): bool
    {
        foreach (self::FIELD_WAJIB as $field) {
            if (!filled($data[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
