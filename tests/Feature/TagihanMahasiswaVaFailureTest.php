<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\TagihanUKT;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class TagihanMahasiswaVaFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_gagal_meminta_va_menampilkan_pesan_dan_tidak_mengubah_tagihan(): void
    {
        $akun = Akun::createAkun([
            'nim' => '255150400111099',
            'nama' => 'Mahasiswa Uji',
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ]);

        $tagihan = TagihanUKT::create([
            'nim' => '255150400111099',
            'semester' => 'Genap 2030/2031',
            'nominal' => 5000000,
            'batas_waktu' => '2031-05-31',
            'status_bayar' => 'Belum Dibayar',
            'virtual_account' => null,
            'midtrans_order_id' => null,
            'va_expires_at' => null,
        ]);

        config()->set('midtrans.is_production', false);

        // Mock saja: tidak mengakses jaringan atau Midtrans yang sebenarnya.
        $this->mock(MidtransService::class, function (MockInterface $mock) use ($tagihan): void {
            $mock->shouldReceive('buatVirtualAccount')
                ->once()
                ->with((int) $tagihan->id_tagihan)
                ->andThrow(new RuntimeException('Simulasi: API Midtrans tidak tersedia.'));
        });

        $response = $this->actingAs($akun)->post(
            route('mahasiswa.tagihan.va', ['idTagihan' => $tagihan->id_tagihan])
        );

        $response->assertRedirect(route('mahasiswa.tagihan.index', [
            'semester' => 'Genap 2030/2031',
        ]));

        $response->assertSessionHas(
            'va_error',
            'Gagal memuat Virtual Account. Periksa konfigurasi Midtrans Sandbox dan log aplikasi, lalu coba lagi.'
        );

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Belum Dibayar',
            'virtual_account' => null,
            'midtrans_order_id' => null,
            'va_expires_at' => null,
        ]);
    }
}
