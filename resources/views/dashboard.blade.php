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


{{-- Menu Pembayaran VA (UC-07): hanya untuk Mahasiswa --}}
@if ($role === \App\Models\Akun::ROLE_MAHASISWA)
<div class="kartu" style="max-width: 440px;">
    <h2>Pembayaran Virtual Account</h2>
    <p class="ket">Lihat tagihan yang belum lunas dan gunakan VA BCA Sandbox sesuai akun login.</p>
    <a class="btn" href="{{ route('mahasiswa.tagihan.index') }}">Buka Pembayaran VA</a>
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


{{-- Menu Penetapan Tagihan UKT (UC-06):
         hanya untuk Bagian Keuangan --}}
@if ($role === \App\Models\Akun::ROLE_KEUANGAN)
<div class="kartu" style="max-width: 440px;">
    <h2>Penetapan Tagihan UKT</h2>

    <p class="ket">
        Kelola dan tetapkan nominal UKT mahasiswa
        berdasarkan periode semester.
    </p>

    <a
        class="btn"
        href="{{ route('keuangan.penetapan-tagihan.index') }}">
        Kelola Tagihan UKT
    </a>
</div>
@endif


    {{-- UC-09: Menu riwayat rekonsiliasi untuk Bagian Keuangan --}}
    @if ($role === \App\Models\Akun::ROLE_KEUANGAN)
        <div class="kartu" style="max-width: 440px;">
            <h2>Riwayat & Hasil Rekonsiliasi</h2>
            <p class="ket">Pantau transaksi pembayaran yang sesuai dan tindak lanjuti transaksi bermasalah.</p>
            <a class="btn" href="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">Lihat Riwayat Rekonsiliasi</a>
        </div>
    @endif
@endsection