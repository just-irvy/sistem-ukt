@extends('layouts.app')

@section('judul', 'Riwayat & Hasil Rekonsiliasi')
@section('peran', 'Bagian Keuangan')

@section('konten')
<style>
    .rr-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin: 22px 0; }
    .rr-metric { background: white; border: 1px solid #e3e7ee; border-radius: 12px; padding: 20px; }
    .rr-metric small { display: block; color: #738096; margin-bottom: 9px; }
    .rr-metric strong { font-size: 22px; color: #172b4d; }
    .rr-filter { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 14px; align-items: end; }
    .rr-filter label { margin: 0 0 6px; display:block; }
    .rr-filter input,.rr-filter select { width:100%; }
    .rr-filter-actions { display:flex; gap:10px; align-items:center; }
    .rr-table { width: 100%; min-width: 920px; }
    .rr-table td { overflow-wrap: anywhere; }
    .rr-flag { display:inline-block; border-radius:20px; padding: 5px 9px; font-size:12px; font-weight:bold; }
    .rr-good { color:#17653a; background:#e5f5eb; }
    .rr-bad { color:#963d24; background:#fff0e0; }
    .rr-link { color:#244b83; font-weight:bold; text-decoration:none; }
    .rr-link:hover {text-decoration:underline;}
    .rr-pagination {display:flex; justify-content:space-between; align-items:center; margin-top:16px; gap:12px;}
    .rr-pagination a {text-decoration:none; color:#244b83; padding:8px 12px; border:1px solid #cfd6e2; border-radius:6px;}
    @media(max-width:920px){.rr-grid{grid-template-columns:repeat(2,1fr)}.rr-filter{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:540px){.rr-grid,.rr-filter{grid-template-columns:1fr}}
</style>

<div class="breadcrumb">Dashboard / Bagian Keuangan / Rekonsiliasi</div>
<h1>Riwayat & Hasil Rekonsiliasi</h1>
<p class="subjudul">Pantau pembayaran yang sesuai dan tindak lanjuti transaksi yang memerlukan pemeriksaan (UC-09).</p>

<div class="rr-grid" aria-label="Ringkasan rekonsiliasi">
    <div class="rr-metric"><small>Jumlah transaksi</small><strong>{{ number_format($ringkasan['total'],0,',','.') }}</strong></div>
    <div class="rr-metric"><small>Rekonsiliasi sesuai</small><strong>{{ number_format($ringkasan['sesuai'],0,',','.') }}</strong></div>
    <div class="rr-metric"><small>Perlu tindak lanjut</small><strong>{{ number_format($ringkasan['bermasalah'],0,',','.') }}</strong></div>
    <div class="rr-metric"><small>Nominal transaksi sesuai</small><strong style="font-size:19px">Rp {{ number_format((float)$ringkasan['nominal_sesuai'],0,',','.') }}</strong></div>
</div>

<div class="kartu">
    <h2>Filter Riwayat Transaksi</h2>
    <form class="rr-filter" method="GET" action="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">
        <div><label for="rr-semester">Periode Semester</label>
            <select id="rr-semester" name="semester">
                <option value="">Semua Semester</option>
                @foreach($semesterOptions as $option)
                    <option value="{{ $option }}" @selected($semester === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="rr-status">Status rekonsiliasi</label>
            <select id="rr-status" name="status">
                <option value="semua" @selected($status === 'semua')>Semua Status</option>
                <option value="sesuai" @selected($status === 'sesuai')>Sesuai</option>
                <option value="bermasalah" @selected($status === 'bermasalah')>Tidak Sesuai / Bermasalah</option>
            </select>
        </div>
        <div><label for="rr-cari">Cari NIM, nomor VA, atau referensi bank</label>
            <input id="rr-cari" name="cari" value="{{ $cari }}" maxlength="64" placeholder="Contoh: NIM atau TRX-...">
        </div>
        <div><label for="rr-mulai">Tanggal transaksi mulai</label><input type="date" id="rr-mulai" name="tanggal_mulai" value="{{ $tanggalMulai }}"></div>
        <div><label for="rr-akhir">Tanggal transaksi akhir</label><input type="date" id="rr-akhir" name="tanggal_akhir" value="{{ $tanggalAkhir }}"></div>
        <div class="rr-filter-actions"><button type="submit" class="btn">Terapkan Filter</button><a class="rr-link" href="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">Reset</a></div>
    </form>
    @if($errors->any())
        <div class="pesan-error-utama" style="margin-top:16px" role="alert">{{ $errors->first() }}</div>
    @endif
</div>

<div class="kartu">
    <h2>Riwayat Transaksi Pembayaran</h2>
    <p class="ket">Status <strong>Tidak Sesuai</strong> ditampilkan sebagai transaksi bermasalah. Nilai sesuai hanya menghitung transaksi dengan hasil rekonsiliasi Sesuai.</p>
    <div class="tabel-bungkus">
        <table class="rr-table">
            <thead><tr>
                <th>Waktu / ID</th><th>Mahasiswa / Semester</th><th>Virtual Account</th>
                <th>Referensi Bank</th><th>Nominal Bayar</th><th>Hasil</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($daftar as $transaksi)
                <tr>
                    <td>{{ $transaksi->waktu_transaksi?->format('d-m-Y H:i') ?? '-' }}<div class="ket">#{{ $transaksi->id_transaksi }}</div></td>
                    <td>
                        @if($transaksi->tagihanUkt)
                            <strong>{{ $transaksi->tagihanUkt->mahasiswa?->nama ?? $transaksi->tagihanUkt->nim }}</strong>
                            <div class="ket">NIM {{ $transaksi->tagihanUkt->nim }}</div>
                            <div class="ket">{{ $transaksi->tagihanUkt->semester }}</div>
                        @else
                            <span class="pesan-error">Tagihan tidak ditemukan</span>
                        @endif
                    </td>
                    <td>{{ $transaksi->virtual_account }}</td>
                    <td>{{ $transaksi->referensi_bank }}</td>
                    <td>Rp {{ number_format((float)$transaksi->nominal_bayar,0,',','.') }}</td>
                    <td><span class="rr-flag {{ $transaksi->status_flag === 'Sesuai' ? 'rr-good' : 'rr-bad' }}">{{ $transaksi->status_flag }}</span></td>
                    <td><a class="rr-link" href="{{ route('keuangan.riwayat-rekonsiliasi.detail', $transaksi->id_transaksi) }}">{{ $transaksi->status_flag === 'Sesuai' ? 'Detail' : 'Tindak Lanjut' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="kosong">Belum ada riwayat transaksi untuk filter yang dipilih.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($daftar->hasPages())
        <div class="rr-pagination">
            <span class="ket">Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }} — {{ $daftar->total() }} transaksi</span>
            <div style="display:flex;gap:8px">
                @if($daftar->previousPageUrl())<a href="{{ $daftar->previousPageUrl() }}">Sebelumnya</a>@endif
                @if($daftar->nextPageUrl())<a href="{{ $daftar->nextPageUrl() }}">Berikutnya</a>@endif
            </div>
        </div>
    @endif
</div>
<p class="ket">Status pembayaran tagihan hanya berubah melalui rekonsiliasi pembayaran tervalidasi (UC-08). Tindak lanjut di halaman ini mencatat hasil pemeriksaan, bukan menyatakan pembayaran lunas secara manual.</p>
@endsection
