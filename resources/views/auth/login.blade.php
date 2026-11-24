@extends('layouts.app')

@section('judul', 'Masuk ke Sistem')
@section('peran', 'Semua pengguna')

{{-- UI-02 | HT-IM-02 | dikirim ke LoginController::submitLogin() --}}
@section('konten')
    <div class="breadcrumb">Beranda / Masuk</div>
    <h1>Masuk ke Sistem</h1>
    <p class="subjudul">Masuk menggunakan kredensial sesuai peran pengguna.</p>

    <div class="auth-layout">
        <aside class="auth-intro" aria-label="Tentang portal">
            <div>
                <span class="section-eyebrow">LAYANAN AKADEMIK</span>
                <h2>Semua informasi UKT dalam satu portal.</h2>
                <p>Periksa tagihan per semester, ajukan penyesuaian, dan pantau proses pembayaran melalui akun yang terdaftar.</p>
            </div>
            <div class="auth-intro-note">Sistem Informasi Pembayaran UKT · Proyek akademik</div>
        </aside>
        <div class="kartu auth-card">
        <h2>Selamat datang kembali</h2>
        <p class="ket">Masukkan identitas dan kata sandi.</p>

        @if (session('status'))
            <div class="pesan-sukses">{{ session('status') }}</div>
        @endif

        {{-- Pesan kesalahan: Username atau Password salah --}}
        @error('login')
            <div class="pesan-error-utama">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div style="margin-bottom: 18px;">
                <label for="username">Username / NIM / NIP</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" maxlength="20" placeholder="Masukkan identitas" required autofocus>
                @error('username') <p class="pesan-error">{{ $message }}</p> @enderror
            </div>
            <div style="margin-bottom: 24px;">
                <label for="password">Kata sandi</label>
                <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>
                @error('password') <p class="pesan-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-lebar">Masuk</button>
        </form>

        <p class="tautan" style="margin-top: 22px;">
            Belum punya akun? <a href="{{ route('registrasi') }}">Daftar sebagai mahasiswa</a>
        </p>
        </div>
    </div>
@endsection
