@extends('layouts.app')

@section('judul', 'Registrasi Akun')
@section('peran', 'Mahasiswa')

{{-- UI-01 | HT-IM-01 | dikirim ke RegistrasiController::submitRegistrasi() --}}
@section('konten')
    <div class="breadcrumb">Beranda / Registrasi</div>
    <h1>Registrasi Akun</h1>
    <p class="subjudul">Buat akun mahasiswa baru dengan data yang valid.</p>

    <div class="tata-letak register-layout">
        <div class="kartu">
            <span class="section-eyebrow">IDENTITAS AKUN</span>
            <h2 style="margin-top: 7px;">Data Mahasiswa</h2>
            <p class="ket">Semua field wajib diisi.</p>

            {{-- Pesan hasil registrasi: Data tidak lengkap / NIM sudah terdaftar --}}
            @error('registrasi')
                <div class="pesan-error-utama">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('registrasi.submit') }}">
                @csrf
                <div class="grid-form">
                    <div>
                        <label for="nama">Nama lengkap</label>
                        <input type="text" id="nama" name="nama" value="{{ old('nama') }}" maxlength="100" placeholder="Masukkan nama lengkap" required>
                        @error('nama') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nim">NIM</label>
                        <input type="text" id="nim" name="nim" value="{{ old('nim') }}" maxlength="15" placeholder="255150400111099" required>
                        @error('nim') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="no_telp">Nomor telepon</label>
                        <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp') }}" maxlength="15" placeholder="08xxxxxxxxxx" required>
                        @error('no_telp') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="fakultas">Fakultas</label>
                        <input type="text" id="fakultas" name="fakultas" value="{{ old('fakultas') }}" maxlength="50" placeholder="Fakultas Ilmu Komputer" required>
                        @error('fakultas') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="prodi">Program studi</label>
                        <input type="text" id="prodi" name="prodi" value="{{ old('prodi') }}" maxlength="50" placeholder="Sistem Informasi" required>
                        @error('prodi') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password">Kata sandi</label>
                        <input type="password" id="password" name="password" minlength="8" maxlength="255" placeholder="Minimal 8 karakter" required>
                        @error('password') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div style="margin-top: 28px;">
                    <button type="submit" class="btn">Daftar Akun</button>
                </div>
            </form>
        </div>

        <div class="kartu">
            <h2>Sudah punya akun?</h2>
            <p class="ket">Login sesuai peran yang terdaftar.</p>
            <a class="btn btn-abu btn-lebar" href="{{ route('login') }}">Masuk</a>
        </div>
    </div>
@endsection
