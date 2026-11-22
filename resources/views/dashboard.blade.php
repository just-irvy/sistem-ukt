@extends('layouts.app')

@section('judul', $judul)
@section('peran', $role)

@section('konten')
    <div class="breadcrumb">Dashboard / {{ $role }}</div>
    <h1>{{ $judul }}</h1>
    <p class="subjudul">Selamat datang, {{ $nama }} ({{ $role }}).</p>

    {{-- Menu Tagihan (UC-03): hanya untuk Mahasiswa --}}
    @if ($role === \App\Models\Akun::ROLE_MAHASISWA)
        <div class="kartu" style="max-width: 440px;">
            <h2>Tagihan Semester</h2>
            <p class="ket">Lihat rincian UKT, batas pembayaran, dan status.</p>
            <a class="btn" href="{{ route('tagihan.index') }}">Lihat Tagihan Semester</a>
        </div>
    @endif

    {{-- Menu Pengajuan Penyesuaian UKT (UC-04): hanya untuk Mahasiswa --}}
    @if ($role === \App\Models\Akun::ROLE_MAHASISWA)
        <div class="kartu" style="max-width: 440px;">
            <h2>Pengajuan Penyesuaian UKT</h2>
            <p class="ket">Ajukan penyesuaian nominal UKT dengan dokumen pendukung.</p>
            <a class="btn" href="{{ route('pengajuan.create') }}">Ajukan Penyesuaian</a>
        </div>
    @endif

    {{-- Menu Verifikasi (UC-05): hanya untuk Verifikator --}}
    @if ($role === \App\Models\Akun::ROLE_VERIFIKATOR)
        <div class="kartu" style="max-width: 440px;">
            <h2>Verifikasi Pengajuan UKT</h2>
            <p class="ket">Tinjau dan putuskan pengajuan penyesuaian UKT mahasiswa.</p>
            <a class="btn" href="{{ route('verifikasi.index') }}">Buka Daftar Pengajuan</a>
        </div>
    @endif
@endsection
