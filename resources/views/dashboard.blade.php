@extends('layouts.app')

@section('judul', $judul)
@section('peran', $role)

@section('konten')
    <div class="breadcrumb">Dashboard / {{ $role }}</div>
    <h1>{{ $judul }}</h1>
    <p class="subjudul">Selamat datang, {{ $nama }} ({{ $role }}). Pilih layanan yang ingin digunakan.</p>

    <h2 style="margin: 0 0 15px;">Layanan utama</h2>
    <section class="menu-grid" aria-label="Menu layanan">
        {{-- Mahasiswa: UC-03, UC-04, dan UC-07 --}}
        @if ($role === \App\Models\Akun::ROLE_MAHASISWA)
            <article class="kartu menu-card">
                <h2>Tagihan Semester</h2>
                <p class="ket">Lihat rincian UKT, batas pembayaran, dan status.</p>
                <a class="btn" href="{{ route('tagihan.index') }}">Lihat Tagihan Semester</a>
            </article>

            <article class="kartu menu-card">
                <h2>Pengajuan Penyesuaian UKT</h2>
                <p class="ket">Ajukan penyesuaian nominal UKT dengan dokumen pendukung.</p>
                <a class="btn" href="{{ route('pengajuan.create') }}">Ajukan Penyesuaian</a>
            </article>

            <article class="kartu menu-card">
                <h2>Pembayaran Virtual Account</h2>
                <p class="ket">Lihat tagihan yang belum lunas dan gunakan VA BCA Sandbox sesuai akun login.</p>
                <a class="btn" href="{{ route('mahasiswa.tagihan.index') }}">Buka Pembayaran VA</a>
            </article>
        @endif

        {{-- Verifikator: UC-05 --}}
        @if ($role === \App\Models\Akun::ROLE_VERIFIKATOR)
            <article class="kartu menu-card">
                <h2>Verifikasi Pengajuan UKT</h2>
                <p class="ket">Tinjau dan putuskan pengajuan penyesuaian UKT mahasiswa.</p>
                <a class="btn" href="{{ route('verifikasi.index') }}">Buka Daftar Pengajuan</a>
            </article>
        @endif

        {{-- Bagian Keuangan: UC-06 dan UC-09 --}}
        @if ($role === \App\Models\Akun::ROLE_KEUANGAN)
            <article class="kartu menu-card">
                <h2>Penetapan Tagihan UKT</h2>
                <p class="ket">Kelola dan tetapkan nominal UKT mahasiswa berdasarkan periode semester.</p>
                <a class="btn" href="{{ route('keuangan.penetapan-tagihan.index') }}">Kelola Tagihan UKT</a>
            </article>

            <article class="kartu menu-card">
                <h2>Riwayat &amp; Hasil Rekonsiliasi</h2>
                <p class="ket">Pantau transaksi pembayaran yang sesuai dan tindak lanjuti transaksi bermasalah.</p>
                <a class="btn" href="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">Lihat Riwayat Rekonsiliasi</a>
            </article>
        @endif
    </section>
@endsection
