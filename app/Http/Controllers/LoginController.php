<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UC-02 Login | SD-02 | PS-02 | UI-02
 */
class LoginController extends Controller
{
    // Menampilkan Form Login UI (UI-02).
    public function tampilkanFormLogin(): View|RedirectResponse
    {
        // Pengguna yang sudah login langsung diarahkan ke dashboard.
        if (Auth::check()) {
            $halaman = $this->tentukanDashboard(Auth::user()->role);

            if ($halaman !== null) {
                return redirect()->route($halaman);
            }
        }

        return view('auth.login');
    }

    // PS-02: autentikasi lalu arahkan ke dashboard sesuai role.
    public function submitLogin(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        $pesanGagal = 'Username atau Password salah';

        // 1. Verifikasi username dan password.
        $statusValid = Akun::verifyCredentials(
            $kredensial['username'],
            $kredensial['password']
        );

        if (!$statusValid) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $pesanGagal]);
        }

        // 2. Ambil role dan tentukan dashboard tujuan.
        $role = Akun::getRole($kredensial['username']);
        $halamanTujuan = $this->tentukanDashboard($role);

        if ($halamanTujuan === null) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Role akun tidak dikenali.']);
        }

        // 3. Mulai sesi login.
        Auth::login(
            Akun::where('username', $kredensial['username'])->first()
        );
        $request->session()->regenerate();

        return redirect()->route($halamanTujuan);
    }

    // Mengakhiri sesi login.
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Menentukan nama route dashboard berdasarkan role.
    private function tentukanDashboard(?string $role): ?string
    {
        return match ($role) {
            Akun::ROLE_MAHASISWA => 'dashboard.mahasiswa',
            Akun::ROLE_KEUANGAN => 'dashboard.keuangan',
            Akun::ROLE_VERIFIKATOR => 'dashboard.verifikator',
            default => null,
        };
    }
}