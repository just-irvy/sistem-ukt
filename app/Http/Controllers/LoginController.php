<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UC-02 Login | PS-02 | UI-02
 */
class LoginController extends Controller
{
    private const PESAN_GAGAL = 'Username atau Password salah';

    // Menampilkan Form Login UI (UI-02).
    public function tampilkanFormLogin(): View
    {
        return view('auth.login');
    }

    // PS-02: memproses kredensial dari Form Login UI.
    public function submitLogin(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($kredensial)) {
            return $this->gagal($request);
        }

        $request->session()->regenerate();

        $tujuan = match (Auth::user()->role) {
            Akun::ROLE_MAHASISWA => 'dashboard.mahasiswa',
            Akun::ROLE_KEUANGAN => 'dashboard.keuangan',
            Akun::ROLE_VERIFIKATOR => 'dashboard.verifikator',
            default => null,
        };

        // Role tidak dikenali: batalkan login.
        if ($tujuan === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->gagal($request);
        }

        return redirect()->route($tujuan);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function gagal(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('username'))
            ->withErrors(['login' => self::PESAN_GAGAL]);
    }
}