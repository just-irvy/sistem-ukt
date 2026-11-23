@extends('layouts.app')

@section('judul', 'Verifikasi Pengajuan UKT')
@section('peran', 'Verifikator UKT')

{{-- UI-05 | FHM-IM-02 | daftar pengajuan masuk --}}
@section('konten')
    <div class="breadcrumb">Dashboard / Verifikasi Pengajuan</div>
    <h1>Verifikasi Pengajuan UKT</h1>
    <p class="subjudul">Tinjau pengajuan penyesuaian UKT dari mahasiswa.</p>

    @if (session('status'))
        <div class="pesan-sukses">{{ session('status') }}</div>
    @endif

    <div class="menu-grid" style="margin-bottom: 20px;">
        <div class="kartu" style="margin: 0;"><div class="ket">Menunggu Verifikasi</div><h1>{{ $jumlahMenunggu }}</h1></div>
        <div class="kartu" style="margin: 0;"><div class="ket">Disetujui</div><h1>{{ $jumlahDisetujui }}</h1></div>
        <div class="kartu" style="margin: 0;"><div class="ket">Ditolak</div><h1>{{ $jumlahDitolak }}</h1></div>
    </div>

    <div class="kartu">
        <h2>Daftar Pengajuan Masuk</h2>
        <div class="tabel-bungkus">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Semester</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->mahasiswa?->nama ?? '-' }}</strong><br>
                                <span class="ket">{{ $item->nim }}</span>
                            </td>
                            <td>{{ $item->semester }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->alasan, 70) }}</td>
                            <td>
                                <span class="status {{ match ($item->status_verifikasi) {
                                    \App\Models\PengajuanUKT::STATUS_DISETUJUI => 'status-setuju',
                                    \App\Models\PengajuanUKT::STATUS_DITOLAK => 'status-tolak',
                                    default => 'status-menunggu',
                                } }}">{{ $item->status_verifikasi }}</span>
                            </td>
                            <td><a class="tautan" href="{{ route('verifikasi.show', $item->id_pengajuan) }}">{{ $item->menunggu() ? 'Tinjau' : 'Lihat' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kosong">Belum ada pengajuan masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
