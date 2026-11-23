<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\TagihanUKT;
use App\Models\TindakLanjutRekonsiliasi;
use App\Models\TransaksiRekonsiliasi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * UC-09 / SD-09 / PS-09: riwayat dan tindak lanjut rekonsiliasi.
 * Tidak menetapkan tagihan Lunas; kewenangan itu milik UC-08.
 */
class RiwayatRekonsiliasiController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanBagianKeuangan();

        $filter = $request->validate([
            'semester' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['semua', 'sesuai', 'bermasalah'])],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'cari' => ['nullable', 'string', 'max:64'],
        ]);

        $semester = $filter['semester'] ?? '';
        $status = $filter['status'] ?? 'semua';
        $tanggalMulai = $filter['tanggal_mulai'] ?? '';
        $tanggalAkhir = $filter['tanggal_akhir'] ?? '';
        $cari = trim($filter['cari'] ?? '');

        // Filter dasar untuk statistik dan daftar. Semua pencarian menggunakan
        // parameter binding Eloquent; pencocokan semester melalui relasi tagihan.
        $dasar = TransaksiRekonsiliasi::query()
            ->when($semester !== '', function ($query) use ($semester) {
                $query->whereHas('tagihanUkt', function ($tagihan) use ($semester) {
                    $tagihan->where('semester', $semester);
                });
            })
            ->when($tanggalMulai !== '', fn ($query) => $query->whereDate('waktu_transaksi', '>=', $tanggalMulai))
            ->when($tanggalAkhir !== '', fn ($query) => $query->whereDate('waktu_transaksi', '<=', $tanggalAkhir))
            ->when($cari !== '', function ($query) use ($cari) {
                // Escape wildcard agar pencarian tetap literal.
                $kata = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cari);
                $pattern = '%' . $kata . '%';
                $query->where(function ($sub) use ($pattern) {
                    $sub->where('virtual_account', 'like', $pattern)
                        ->orWhere('referensi_bank', 'like', $pattern)
                        ->orWhereHas('tagihanUkt', fn ($tagihan) => $tagihan->where('nim', 'like', $pattern));
                });
            });

        $ringkasan = [
            'total' => (clone $dasar)->count(),
            'sesuai' => (clone $dasar)->where('status_flag', 'Sesuai')->count(),
            'bermasalah' => (clone $dasar)->where('status_flag', 'Tidak Sesuai')->count(),
            'nominal_sesuai' => (clone $dasar)->where('status_flag', 'Sesuai')->sum('nominal_bayar'),
        ];

        $daftar = (clone $dasar)
            ->with('tagihanUkt.mahasiswa')
            ->when($status === 'sesuai', fn ($query) => $query->where('status_flag', 'Sesuai'))
            ->when($status === 'bermasalah', fn ($query) => $query->where('status_flag', 'Tidak Sesuai'))
            ->orderByDesc('waktu_transaksi')
            ->orderByDesc('id_transaksi')
            ->paginate(15)
            ->withQueryString();

        $semesterOptions = TagihanUKT::query()
            ->select('semester')->distinct()->orderBy('semester')->pluck('semester');

        return view('keuangan.riwayat_rekonsiliasi.index', compact(
            'daftar', 'ringkasan', 'semesterOptions', 'semester', 'status',
            'tanggalMulai', 'tanggalAkhir', 'cari'
        ));
    }

    public function detail(int $idTransaksi): View
    {
        $this->pastikanBagianKeuangan();

        $transaksi = TransaksiRekonsiliasi::with('tagihanUkt.mahasiswa')
            ->findOrFail($idTransaksi);
        $riwayatTindakLanjut = TindakLanjutRekonsiliasi::with('akun')
            ->where('id_transaksi', $transaksi->id_transaksi)
            ->orderByDesc('id_tindak_lanjut')
            ->get();

        return view('keuangan.riwayat_rekonsiliasi.detail', compact('transaksi', 'riwayatTindakLanjut'));
    }

    public function simpanTindakLanjut(Request $request, int $idTransaksi): RedirectResponse
    {
        $this->pastikanBagianKeuangan();

        $transaksi = TransaksiRekonsiliasi::findOrFail($idTransaksi);
        abort_unless($transaksi->status_flag === 'Tidak Sesuai', 403);

        $data = $request->validate([
            'status_tindak_lanjut' => [
                'required', Rule::in(['Dalam Penanganan', 'Selesai Ditinjau']),
            ],
            'catatan' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Append-only audit trail. Tidak menyunting referensi transaksi,
        // status rekonsiliasi, atau status pembayaran tagihan.
        DB::transaction(function () use ($transaksi, $data) {
            TindakLanjutRekonsiliasi::create([
                'id_transaksi' => $transaksi->id_transaksi,
                'id_akun' => Auth::id(),
                'status_tindak_lanjut' => $data['status_tindak_lanjut'],
                'catatan' => trim($data['catatan']),
            ]);
        });

        return redirect()->route('keuangan.riwayat-rekonsiliasi.detail', $idTransaksi)
            ->with('sukses', 'Catatan tindak lanjut berhasil disimpan.');
    }

    private function pastikanBagianKeuangan(): void
    {
        abort_unless(Auth::user()?->role === Akun::ROLE_KEUANGAN, 403);
    }
}
