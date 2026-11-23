@extends('layouts.app')

@section('judul', 'Detail Rekonsiliasi')
@section('peran', 'Bagian Keuangan')

@section('konten')
<style>
    .rd-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 24px; }
    .rd-grid dt { color:#738096; font-size:13px; margin:0 0 5px; }
    .rd-grid dd { margin:0; overflow-wrap:anywhere; font-weight:600; }
    .rd-label {display:block; font-weight:600; margin-bottom:7px;}
    .rd-input {width:100%; padding:11px; border:1px solid #cfd6e2; border-radius:8px; font:inherit;}
    .rd-note {border-left:3px solid #dbe5f2; padding:8px 15px; margin-bottom:14px; overflow-wrap:anywhere;}
    .rd-pre {white-space:pre-wrap; font:inherit; margin:7px 0;}
    @media(max-width:680px){.rd-grid{grid-template-columns:1fr}}
</style>

<div class="breadcrumb">Dashboard / Bagian Keuangan / <a href="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">Riwayat Rekonsiliasi</a> / Detail</div>
<h1>Detail Transaksi #{{ $transaksi->id_transaksi }}</h1>
<p class="subjudul">Periksa hasil rekonsiliasi dan rekam tindak lanjut tanpa mengubah bukti transaksi dari bank.</p>

<div class="kartu">
    <h2>Data Transaksi</h2>
    <dl class="rd-grid">
        <div><dt>Status Rekonsiliasi</dt><dd>{{ $transaksi->status_flag }}</dd></div>
        <div><dt>Status Tagihan</dt><dd>{{ $transaksi->tagihanUkt?->status_bayar ?? 'Tagihan tidak ditemukan' }}</dd></div>
        <div><dt>Nomor Virtual Account</dt><dd>{{ $transaksi->virtual_account }}</dd></div>
        <div><dt>Referensi Bank</dt><dd>{{ $transaksi->referensi_bank }}</dd></div>
        <div><dt>Waktu Transaksi</dt><dd>{{ $transaksi->waktu_transaksi?->format('d-m-Y H:i:s') ?? '-' }}</dd></div>
        <div><dt>Nominal Dibayar</dt><dd>Rp {{ number_format((float)$transaksi->nominal_bayar,0,',','.') }}</dd></div>
        <div><dt>Mahasiswa</dt><dd>{{ $transaksi->tagihanUkt?->mahasiswa?->nama ?? '-' }} @if($transaksi->tagihanUkt)(NIM {{ $transaksi->tagihanUkt->nim }})@endif</dd></div>
        <div><dt>Semester</dt><dd>{{ $transaksi->tagihanUkt?->semester ?? '-' }}</dd></div>
        <div><dt>Nominal Tagihan</dt><dd>{{ $transaksi->tagihanUkt ? 'Rp '.number_format((float)$transaksi->tagihanUkt->nominal,0,',','.') : '-' }}</dd></div>
    </dl>
</div>

@if(session('sukses'))<div class="pesan-sukses" role="status">{{ session('sukses') }}</div>@endif

@if($transaksi->status_flag === 'Tidak Sesuai')
<div class="kartu">
    <h2>Catat Tindak Lanjut</h2>
    <p class="ket">Isi catatan hasil pemeriksaan atau rencana tindak lanjut. Catatan disimpan sebagai riwayat audit. Tombol ini <strong>tidak</strong> mengubah status pembayaran menjadi Lunas.</p>
    @if($errors->any())<div class="pesan-error-utama" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('keuangan.riwayat-rekonsiliasi.tindak-lanjut', $transaksi->id_transaksi) }}">
        @csrf
        <div style="margin-bottom:15px">
            <label class="rd-label" for="rd-status">Status tindak lanjut</label>
            <select class="rd-input" id="rd-status" name="status_tindak_lanjut" required>
                <option value="Dalam Penanganan" @selected(old('status_tindak_lanjut') === 'Dalam Penanganan')>Dalam Penanganan</option>
                <option value="Selesai Ditinjau" @selected(old('status_tindak_lanjut') === 'Selesai Ditinjau')>Selesai Ditinjau (bukan berarti lunas)</option>
            </select>
        </div>
        <div style="margin-bottom:15px">
            <label class="rd-label" for="rd-catatan">Catatan / koreksi administratif</label>
            <textarea class="rd-input" id="rd-catatan" name="catatan" minlength="10" maxlength="2000" required rows="4" placeholder="Tuliskan alasan masalah, hasil pemeriksaan, atau tindak lanjut kepada pihak terkait...">{{ old('catatan') }}</textarea>
        </div>
        <button class="btn" type="submit">Simpan Tindak Lanjut</button>
    </form>
</div>
@endif

<div class="kartu">
    <h2>Riwayat Tindak Lanjut</h2>
    @forelse($riwayatTindakLanjut as $catatan)
        <div class="rd-note">
            <strong>{{ $catatan->status_tindak_lanjut }}</strong>
            <div class="ket">{{ $catatan->created_at?->format('d-m-Y H:i') ?? '-' }} — {{ $catatan->akun?->username ?? 'Akun tidak tersedia' }}</div>
            <p class="rd-pre">{{ $catatan->catatan }}</p>
        </div>
    @empty
        <p class="ket">Belum ada catatan tindak lanjut untuk transaksi ini.</p>
    @endforelse
</div>
<a class="btn btn-abu" href="{{ route('keuangan.riwayat-rekonsiliasi.index') }}">Kembali ke Riwayat</a>
@endsection
