<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UC-03 Lihat Tagihan Semester | SD-03 | PS-07 | UI-03
 */
class TagihanController extends Controller
{
    // UI-03: halaman tagihan. Semester dipilih lewat dropdown (?semester=...).
    public function tampilkanHalamanTagihan(Request $request): View
    {
        $request->validate([
            'semester' => ['nullable', 'string', 'max:20'],
        ]);

        $mahasiswa = $this->ambilMahasiswa();

        // Hanya mahasiswa yang boleh membuka halaman ini.
        abort_if($mahasiswa === null, 403, 'Hanya mahasiswa yang dapat melihat tagihan.');

        $semester = $request->query('semester');
        $tagihan = null;

        if (filled($semester)) {
            $tagihan = TagihanUKT::getTagihanSemester($mahasiswa->nim, $semester);
        }

        return view('tagihan', [
            'mahasiswa' => $mahasiswa,
            'daftarSemester' => TagihanUKT::getDaftarSemester($mahasiswa->nim),
            'semesterDipilih' => $semester,
            'tagihan' => $tagihan,
            // true jika semester sudah dipilih tetapi tagihan belum ditetapkan.
            'tagihanBelumTersedia' => filled($semester) && $tagihan === null,
        ]);
    }

    // PS-07: menampilkan tagihan UKT mahasiswa yang sedang login (JSON).
    public function getTagihanSemester(Request $request): JsonResponse
    {
        $request->validate([
            'semester' => ['required', 'string', 'max:20'],
        ]);

        // NIM diambil dari akun yang login, bukan dari input pengguna,
        // sehingga mahasiswa tidak dapat melihat tagihan mahasiswa lain.
        $mahasiswa = $this->ambilMahasiswa();

        if ($mahasiswa === null) {
            return response()->json([
                'message' => 'Hanya mahasiswa yang dapat melihat tagihan.',
            ], 403);
        }

        $dataTagihan = TagihanUKT::getTagihanSemester(
            $mahasiswa->nim,
            $request->query('semester')
        );

        if ($dataTagihan === null) {
            return response()->json([
                'message' => 'Tagihan belum tersedia',
            ], 404);
        }

        return response()->json([
            'data' => [
                'nim' => $dataTagihan->nim,
                'semester' => $dataTagihan->semester,
                'nominal' => $dataTagihan->nominal,
                'batas_waktu' => $dataTagihan->batas_waktu->format('Y-m-d'),
                'status_bayar' => $dataTagihan->status_bayar,
                'virtual_account' => $dataTagihan->virtual_account,
            ],
        ], 200);
    }

    // Data mahasiswa dari akun yang login; null jika bukan akun mahasiswa.
    private function ambilMahasiswa(): ?Mahasiswa
    {
        $akun = Auth::user();

        if ($akun->role !== Akun::ROLE_MAHASISWA) {
            return null;
        }

        return $akun->mahasiswa;
    }
}
