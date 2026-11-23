@extends('layouts.app')

@section('judul', 'Detail Pengajuan UKT')
@section('peran', 'Verifikator UKT')

{{-- UI-05 | FHM-IM-03 | dikirim ke VerifikasiController::proses() --}}
@section('konten')
    <div class="breadcrumb">Dashboard / Verifikasi Pengajuan / Detail</div>
    <h1>Detail Pengajuan</h1>
    <p class="subjudul">Periksa alasan dan dokumen pendukung sebelum mengambil keputusan.</p>

    @error('pengajuan') <div class="pesan-error-utama">{{ $message }}</div> @enderror
    @error('tagihan') <div class="pesan-error-utama">{{ $message }}</div> @enderror
    @error('keputusan') <div class="pesan-error-utama">{{ $message }}</div> @enderror

    <div class="kartu">
        <h2>Data Pengajuan</h2>
        <dl class="detail">
            <dt>Mahasiswa</dt><dd>{{ $pengajuan->mahasiswa?->nama ?? '-' }} ({{ $pengajuan->nim }})</dd>
            <dt>Semester</dt><dd>{{ $pengajuan->semester }}</dd>
            <dt>Status</dt>
            <dd>
                <span class="status {{ match ($pengajuan->status_verifikasi) {
                    \App\Models\PengajuanUKT::STATUS_DISETUJUI => 'status-setuju',
                    \App\Models\PengajuanUKT::STATUS_DITOLAK => 'status-tolak',
                    default => 'status-menunggu',
                } }}">{{ $pengajuan->status_verifikasi }}</span>
            </dd>
            <dt>Alasan</dt><dd style="white-space: pre-line;">{{ $pengajuan->alasan }}</dd>
            <dt>Dokumen pendukung</dt>
            <dd><a class="tautan" target="_blank" rel="noopener" href="{{ route('verifikasi.dokumen', $pengajuan->id_pengajuan) }}">Lihat dokumen</a></dd>
            <dt>Nominal UKT saat ini</dt>
            <dd>
                @if ($tagihan)
                    Rp {{ number_format((float) $tagihan->nominal, 0, ',', '.') }} ({{ $tagihan->status_bayar }})
                @else
                    Tagihan belum tersedia
                @endif
            </dd>
            @unless ($pengajuan->menunggu())
                <dt>Catatan verifikator</dt><dd>{{ $pengajuan->catatan_verifikator ?? '-' }}</dd>
            @endunless
        </dl>
    </div>

    @if ($pengajuan->menunggu())
        <div class="kartu">
            <h2>Keputusan Verifikasi</h2>
            <p class="ket">Setujui dengan nominal UKT baru, atau tolak disertai catatan.</p>

            <form method="POST" action="{{ route('verifikasi.proses', $pengajuan->id_pengajuan) }}">
                @csrf
                <div class="grid-form">
                    <div>
                        <label for="nominal_baru">Nominal UKT baru (Rp)</label>
                        <input type="number" id="nominal_baru" name="nominal_baru" min="1" step="1" value="{{ old('nominal_baru', $tagihan ? (int) $tagihan->nominal : '') }}" placeholder="Diisi saat menyetujui">
                        @error('nominal_baru') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="penuh">
                        <label for="catatan">Catatan verifikator</label>
                        <textarea id="catatan" name="catatan" maxlength="1000" placeholder="Wajib diisi saat menolak pengajuan">{{ old('catatan') }}</textarea>
                        @error('catatan') <p class="pesan-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="aksi" style="margin-top: 20px;">
                    <button type="submit" name="keputusan" value="{{ \App\Models\PengajuanUKT::STATUS_DISETUJUI }}" class="btn btn-hijau">Setujui</button>
                    <button type="submit" name="keputusan" value="{{ \App\Models\PengajuanUKT::STATUS_DITOLAK }}" class="btn btn-merah">Tolak</button>
                    <a href="{{ route('verifikasi.index') }}" class="btn btn-abu">Kembali</a>
                </div>
            </form>
        </div>
    @else
        <div class="aksi"><a href="{{ route('verifikasi.index') }}" class="btn btn-abu">Kembali</a></div>
    @endif
@endsection
