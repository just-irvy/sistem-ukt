@extends('layouts.app')

@section('judul', $judul)
@section('peran', $role)

@section('konten')
    <div class="breadcrumb">Dashboard / {{ $role }}</div>
    <section class="dashboard-hero" aria-labelledby="page-title">
        <span class="section-eyebrow">PORTAL SISTEM UKT <span aria-hidden="true">/</span> {{ $role }}</span>
        <div class="dashboard-hero-main">
            <div>
                <h1 id="page-title">{{ $judul }}</h1>
                <p>Selamat datang, <strong>{{ $nama }}</strong>. Pilih layanan yang ingin digunakan melalui menu di bawah.</p>
            </div>
            <span class="hero-role-label">{{ $role }}</span>
        </div>
        <div class="hero-rule" aria-hidden="true"></div>
    </section>

    <div class="section-heading">
        <div>
            <span class="section-eyebrow">AKSES CEPAT</span>
            <h2>Layanan utama</h2>
        </div>
        <span class="section-caption">Sesuai kewenangan akun</span>
    </div>

    <section class="menu-grid" aria-label="Menu layanan">
        {{-- Mahasiswa: UC-03, UC-04, UC-07 --}}
        @if ($role === \App\Models\Akun::ROLE_MAHASISWA)
            <article class="kartu menu-card">
                <div class="menu-card-top"><span>01 / AKADEMIK</span><span aria-hidden="true">↗</span></div>
                <h2>Tagihan Semester</h2>
                <p class="ket">Lihat rincian UKT, batas pembayaran, dan status.</p>
                <a class="btn" href="{{ route('tagihan.index') }}">Lihat Tagihan Semester</a>
            </article>
            <article class="kartu menu-card">
                <div class="menu-card-top"><span>02 / PENGAJUAN</span><span aria-hidden="true">↗</span></div>
                <h2>Pengajuan Penyesuaian UKT</h2>
                <p class="ket">Ajukan penyesuaian nominal UKT dengan dokumen pendukung.</p>
                <a class="btn" href="{{ route('pengajuan.create') }}">Ajukan Penyesuaian</a>
            </article>
            <article class="kartu menu-card">
                <div class="menu-card-top"><span>03 / PEMBAYARAN</span><span aria-hidden="true">↗</span></div>
                <h2>Pembayaran Virtual Account</h2>
                <p class="ket">Lihat tagihan yang belum lunas dan gunakan VA BCA Sandbox sesuai akun login.</p>
                <a class="btn" href="{{ route('mahasiswa.tagihan.index') }}">Buka Pembayaran VA</a>
            </article>
        @endif

        {{-- Verifikator: UC-05 --}}
        @if ($role === \App\Models\Akun::ROLE_VERIFIKATOR)
            <article class="kartu menu-card">
                <div class="menu-card-top"><span>01 / VERIFIKASI</span><span aria-hidden="true">↗</span></div>
                <h2>Verifikasi Pengajuan UKT</h2>
                <p class="ket">Tinjau dan putuskan pengajuan penyesuaian UKT mahasiswa.</p>
                <a class="btn" href="{{ route('verifikasi.index') }}">Buka Daftar Pengajuan</a>
            </article>
        @endif

        {{-- Bagian Keuangan: UC-06 dan UC-09 --}}
        @if ($role === \App\Models\Akun::ROLE_KEUANGAN)
            <article class="kartu menu-card">
                <div class="menu-card-top"><span>01 / ADMINISTRASI</span><span aria-hidden="true">↗</span></div>
                <h2>Penetapan Tagihan UKT</h2>
                <p class="ket">Kelola dan tetapkan nominal UKT mahasiswa berdasarkan periode semester.</p>
                <a class="btn" href="{{ route('keuangan.penetapan-tagihan.index') }}">Kelola Tagihan UKT</a>
            </article>
            <article class="kartu menu-card">
                <div class="menu-card-top"><span>02 / REKONSILIASI</span><span aria-hidden="true">↗</span></div>
                <h2>Riwayat &amp; Hasil Rekonsiliasi</h2>
                <p class="ket">Pantau transaksi pembayaran yang sesuai dan tindak lanjuti transaksi bermasalah.</p>
                <a class="btn" href="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">Lihat Riwayat Rekonsiliasi</a>
            </article>
        @endif
    </section>
@endsection
