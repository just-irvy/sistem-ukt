@extends('layouts.app')

@section('judul', 'Pengajuan Penyesuaian UKT')
@section('peran', 'Mahasiswa')

{{-- UI-04 | FHM-IM-01 | dikirim ke PengajuanController::store() --}}
@section('konten')
    <div class="breadcrumb">Dashboard / Pengajuan Penyesuaian UKT</div>
    <h1>Pengajuan Penyesuaian UKT</h1>
    <p class="subjudul">Ajukan penyesuaian nominal UKT dengan alasan dan dokumen pendukung.</p>

    @if (session('status'))
        <div class="pesan-sukses">{{ session('status') }}</div>
    @endif

    <div class="kartu">
        <h2>Form Pengajuan</h2>
        <p class="ket">Semua isian wajib diisi. Dokumen berformat PDF atau JPG, maksimal 2 MB.</p>

        @if ($tagihan->isEmpty())
            <div class="pesan-error-utama">Belum ada tagihan yang dapat diajukan penyesuaiannya.</div>
        @endif

        <form method="POST" action="{{ route('pengajuan.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid-form">
                <div class="penuh">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required @disabled($tagihan->isEmpty())>
                        <option value="">Pilih semester</option>
                        @foreach ($tagihan as $item)
                            <option value="{{ $item->semester }}" @selected(old('semester') === $item->semester)>
                                {{ $item->semester }} - Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    @error('semester') <p class="pesan-error">{{ $message }}</p> @enderror
                </div>

                <div class="penuh">
                    <label for="alasan">Alasan pengajuan</label>
                    <textarea id="alasan" name="alasan" maxlength="2000" placeholder="Jelaskan alasan pengajuan penyesuaian UKT" required>{{ old('alasan') }}</textarea>
                    @error('alasan') <p class="pesan-error">{{ $message }}</p> @enderror
                </div>

                <div class="penuh">
                    <label for="dokumen">Dokumen pendukung</label>
                    <input type="file" id="dokumen" name="dokumen" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" required>
                    {{-- Indikator dokumen berhasil dilampirkan (langkah 6 UC-04) --}}
                    <p id="indikator-dokumen" class="ket" style="margin: 8px 0 0;"></p>
                    @error('dokumen') <p class="pesan-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="aksi" style="margin-top: 20px;">
                <button type="submit" class="btn" @disabled($tagihan->isEmpty())>Kirim Pengajuan</button>
                <a href="{{ route('dashboard.mahasiswa') }}" class="btn btn-abu">Kembali</a>
            </div>
        </form>
    </div>

    <div class="kartu">
        <h2>Riwayat Pengajuan</h2>
        <p class="ket">Status pengajuan yang pernah Anda kirim.</p>
        <div class="tabel-bungkus">
            <table>
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Catatan Verifikator</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $item)
                        <tr>
                            <td>{{ $item->semester }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->alasan, 80) }}</td>
                            <td>
                                <span class="status {{ match ($item->status_verifikasi) {
                                    \App\Models\PengajuanUKT::STATUS_DISETUJUI => 'status-setuju',
                                    \App\Models\PengajuanUKT::STATUS_DITOLAK => 'status-tolak',
                                    default => 'status-menunggu',
                                } }}">{{ $item->status_verifikasi }}</span>
                            </td>
                            <td>{{ $item->catatan_verifikator ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="kosong">Belum ada pengajuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Pemeriksaan format dan ukuran di sisi browser; server tetap memvalidasi ulang.
        (function () {
            var input = document.getElementById('dokumen');
            var info = document.getElementById('indikator-dokumen');
            var maks = {{ \App\Services\PengajuanService::MAKS_UKURAN_KB }} * 1024;

            input.addEventListener('change', function () {
                var file = input.files[0];
                if (!file) { info.textContent = ''; return; }

                var ok = /\.(pdf|jpe?g)$/i.test(file.name);
                if (!ok || file.size > maks) {
                    info.style.color = '#b3392f';
                    info.textContent = 'File ditolak: harus PDF/JPG dan maksimal 2 MB.';
                    input.value = '';
                    return;
                }

                info.style.color = '#207744';
                info.textContent = 'Dokumen terlampir: ' + file.name + ' (' + Math.ceil(file.size / 1024) + ' KB)';
            });
        })();
    </script>
@endsection
