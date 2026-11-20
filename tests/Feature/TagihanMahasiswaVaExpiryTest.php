<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TagihanMahasiswaVaExpiryTest extends TestCase
{
    protected function tearDown(): void
    {
        // Hindari waktu simulasi memengaruhi test lain.
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_sebelum_kedaluwarsa_menampilkan_keterangan_belum_terlewati(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-11-21 08:24:24', 'Asia/Jakarta')
        );

        $response = $this->bukaHalamanTagihanUji('2026-11-21 08:24:25');

        $response->assertOk();
        $response->assertSee('21-11-2026 08:24:25');
        $response->assertSee('Masa berlaku belum terlewati berdasarkan waktu lokal yang tersimpan.');
        $response->assertDontSee('Masa berlaku VA telah lewat berdasarkan waktu yang tersimpan.');
    }

    public function test_tepat_pada_batas_kedaluwarsa_menampilkan_peringatan(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-11-21 08:24:25', 'Asia/Jakarta')
        );

        $response = $this->bukaHalamanTagihanUji('2026-11-21 08:24:25');

        $response->assertOk();
        $response->assertSee('Masa berlaku VA telah lewat berdasarkan waktu yang tersimpan.');
        $response->assertSee('Sistem tidak otomatis menghapus atau menerbitkan VA pengganti.');
        $response->assertDontSee('Bayar via Virtual Account (BCA Sandbox)');
    }

    public function test_va_lama_tanpa_metadata_menampilkan_keterangan_yang_tepat(): void
    {
        $response = $this->bukaHalamanTagihanUji(null);

        $response->assertOk();
        $response->assertSee('Masa berlaku VA ini belum tercatat di database (misalnya VA lama).');
        $response->assertDontSee('Bayar via Virtual Account (BCA Sandbox)');
    }

    /**
     * Render Blade UI-03 melalui route uji, tanpa memanggil controller,
     * mengakses database, atau menghubungi Midtrans.
     */
    private function bukaHalamanTagihanUji(?string $vaExpiresAt)
    {
        Route::middleware('web')->get('/__uji-uc07/masa-berlaku', static function () use ($vaExpiresAt) {
            $mahasiswa = new Mahasiswa([
                'nim' => '255150400111099',
                'nama' => 'Mahasiswa Uji',
            ]);

            $tagihan = new TagihanUKT([
                'nim' => $mahasiswa->nim,
                'semester' => 'Genap 2027/2028',
                'nominal' => 5000000,
                'batas_waktu' => '2028-05-31',
                'status_bayar' => 'Belum Dibayar',
                'virtual_account' => '53113794609664399508145',
                'midtrans_order_id' => 'UKT-TEST-EXPIRY',
                'va_expires_at' => $vaExpiresAt,
            ]);

            return view('mahasiswa.tagihan', [
                'nim' => $mahasiswa->nim,
                'mahasiswa' => $mahasiswa,
                'semester' => $tagihan->semester,
                'semesterOptions' => collect([$tagihan->semester]),
                'tagihan' => $tagihan,
            ]);
        })->name('mahasiswa.tagihan.index');

        // Route uji ditambahkan setelah aplikasi Laravel selesai boot.
        Route::getRoutes()->refreshNameLookups();

        return $this->get('/__uji-uc07/masa-berlaku');
    }
}
