<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use App\Services\PenetapanTagihanService;
use Illuminate\Http\Request;

class PenetapanTagihanController extends Controller
{
    protected PenetapanTagihanService $penetapanService;

    public function __construct(PenetapanTagihanService $penetapanService)
    {
        $this->penetapanService = $penetapanService;
    }

    /**
     * Menampilkan halaman penetapan tagihan UKT.
     */
    public function index(Request $request)
    {
        $this->pastikanBagianKeuangan($request);

        $currentYear = now()->year;
        $generatedSemesters = [];

        // Menyediakan pilihan periode semester.
        for ($i = 0; $i < 4; $i++) {
            $startYear = $currentYear + $i;
            $endYear = $startYear + 1;

            $generatedSemesters[] = "Ganjil {$startYear}/{$endYear}";
            $generatedSemesters[] = "Genap {$startYear}/{$endYear}";
        }

        // Menggabungkan semester pilihan dengan semester
        // yang sudah tersimpan dalam database.
        $storedSemesters = TagihanUKT::query()
            ->select('semester')
            ->distinct()
            ->pluck('semester');

        $semesterOptions = collect($generatedSemesters)
            ->merge($storedSemesters)
            ->unique()
            ->values();

        $request->validate([
            'semester' => 'nullable|string|max:20',
        ]);

        $semester = $request->query(
            'semester',
            $semesterOptions->first()
        );

        if (!$semesterOptions->contains($semester)) {
            $semesterOptions->prepend($semester);
        }

        // Mengambil seluruh mahasiswa yang terdaftar.
        $mahasiswa = Mahasiswa::orderBy('nama')
            ->orderBy('nim')
            ->get();

        // Mengambil tagihan pada semester yang dipilih.
        $tagihanByNim = TagihanUKT::where('semester', $semester)
            ->get()
            ->keyBy('nim');

        return view('keuangan.penetapan-tagihan', [
            'mahasiswa' => $mahasiswa,
            'semester' => $semester,
            'semesterOptions' => $semesterOptions,
            'tagihanByNim' => $tagihanByNim,
        ]);
    }

    /**
     * Menyimpan penetapan tagihan UKT.
     */
    public function store(Request $request)
    {
        $this->pastikanBagianKeuangan($request);

        $validated = $request->validate([
            'nim' => 'required|string|exists:mahasiswa,nim',
            'semester' => 'required|string|max:20',
            'nominal' => 'required|numeric|min:0.01|max:9999999999.99',
            'batas_waktu' => 'required|date|after_or_equal:today',
        ], [
            'nim.exists' => 'Data mahasiswa tidak ditemukan.',
            'nominal.required' => 'Nominal UKT wajib diisi.',
            'nominal.numeric' => 'Nominal UKT harus berupa angka.',
            'nominal.min' => 'Nominal UKT harus lebih besar dari nol.',
            'batas_waktu.after_or_equal' => 'Batas pembayaran tidak boleh lebih awal dari hari ini.',
        ]);

        $this->penetapanService->savePenetapanTagihan($validated);

        return redirect()
            ->route('keuangan.penetapan-tagihan.index', [
                'semester' => $validated['semester'],
            ])
            ->with('success', 'Penetapan tagihan UKT berhasil disimpan.');
    }

    /** Hak melihat dan menetapkan UKT hanya untuk Bagian Keuangan. */
    private function pastikanBagianKeuangan(Request $request): void
    {
        abort_unless(
            $request->user()?->role === Akun::ROLE_KEUANGAN,
            403
        );
    }
}
