@extends('layouts.app')

@section('judul', 'Tagihan Semester')
@section('peran', 'Mahasiswa')

{{-- UI-03 | dikirim dari TagihanController::tampilkanHalamanTagihan() --}}
@section('konten')
    <div class="breadcrumb">Dashboard / Tagihan Semester</div>
    <h1>Tagihan Semester</h1>
    <p class="subjudul">Lihat tagihan UKT {{ $mahasiswa->nama }} per semester.</p>

    <div class="kartu" style="max-width: 560px; margin-bottom: 16px;">
        <h2>Cari Tagihan</h2>
        <form method="GET" action="{{ route('tagihan.index') }}">
            <label for="semester">Periode semester</label>
            <select id="semester" name="semester" style="width: 100%; padding: 8px; margin-bottom: 16px;">
                <option value="">Pilih semester</option>
                @foreach ($daftarSemester as $item)
                    <option value="{{ $item }}" @selected($item === $semesterDipilih)>{{ $item }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn">Tampilkan Tagihan</button>
        </form>
    </div>

    @if ($tagihan)
        <div class="kartu" style="max-width: 560px;">
            <h2>Rincian Tagihan</h2>
            <p>Semester: {{ $tagihan->semester }}</p>
            <p>Nominal: Rp {{ number_format((float) $tagihan->nominal, 0, ',', '.') }}</p>
            <p>Batas waktu: {{ $tagihan->batas_waktu->format('d-m-Y') }}</p>
            <p>Status: {{ $tagihan->status_bayar }}</p>
            @if ($tagihan->status_bayar === 'Belum Dibayar')
                <a class="btn" href="{{ route('mahasiswa.tagihan.index', ['semester' => $tagihan->semester]) }}">
                    Lanjut ke Pembayaran Virtual Account
                </a>
            @endif
        </div>
    @elseif ($tagihanBelumTersedia)
        <div class="kartu" style="max-width: 560px;">
            <p>Tagihan belum tersedia</p>
        </div>
    @endif
@endsection