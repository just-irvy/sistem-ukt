<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UC-02 Login | SD-02 | PS-02 | UI-02
 *
 * Integrasi autentikasi HT dan FHM.
 */
class LoginController extends Controller
{
    private const PESAN_GAGAL = 'Username atau Password salah';

    /**
     * Menampilkan halaman login.
     * Pengguna yang sudah login diarahkan ke dashboard.
     */
    public function tampilkanFormLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            $tujuan = $this->tentukanDashboard(Auth::user()->role);

            if ($tujuan !== null) {
                return redirect()->route($tujuan);
            }
        }

        return view('auth.login');
    }

    /**
     * PS-02: Validasi kredensial dan redirect sesuai role.
     */
    public function submitLogin(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        // Autentikasi menggunakan guard web dan model Akun.
        if (!Auth::attempt($kredensial)) {
            return $this->gagal($request);
        }

        // Tentukan dashboard berdasarkan role.
        $tujuan = $this->tentukanDashboard(Auth::user()->role);

        // Jika role tidak dikenal, batalkan autentikasi.
        if ($tujuan === null) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => 'Role akun tidak dikenali.',
                ]);
        }

        // Mencegah session fixation setelah login.
        $request->session()->regenerate();

        return redirect()->route($tujuan);
    }

    /**
     * Mengakhiri sesi pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Menentukan dashboard sesuai role.
     */
    private function tentukanDashboard(?string $role): ?string
    {
        return match ($role) {
            Akun::ROLE_MAHASISWA => 'dashboard.mahasiswa',
            Akun::ROLE_KEUANGAN => 'dashboard.keuangan',
            Akun::ROLE_VERIFIKATOR => 'dashboard.verifikator',
            default => null,
        };
    }

    /**
     * Mengembalikan pesan ketika autentikasi gagal.
     */
    private function gagal(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('username'))
            ->withErrors([
                'login' => self::PESAN_GAGAL,
            ]);
    }
}
