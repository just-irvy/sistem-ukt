<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\TagihanUKT;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TagihanMahasiswaAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mahasiswa(string $nim): Akun
    {
        return Akun::createAkun([
            'nim' => $nim,
            'nama' => 'Mahasiswa '.$nim,
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ]);
    }

    private function tagihan(string $nim, string $semester = 'Ganjil 2026/2027', string $status = 'Belum Dibayar', ?string $va = null): TagihanUKT
    {
        return TagihanUKT::create([
            'nim' => $nim,
            'semester' => $semester,
            'nominal' => 5000000,
            'batas_waktu' => '2026-11-30',
            'status_bayar' => $status,
            'virtual_account' => $va,
            'midtrans_order_id' => null,
            'va_expires_at' => null,
        ]);
    }

    public function test_tamu_harus_login_untuk_lihat_dan_minta_va(): void
    {
        $this->get(route('mahasiswa.tagihan.index'))
            ->assertRedirect(route('login'));

        $this->post(route('mahasiswa.tagihan.va', ['idTagihan' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_hanya_mahasiswa_boleh_melihat_dan_meminta_va(): void
    {
        $keuangan = Akun::create([
            'username' => 'pegawai.keuangan',
            'password' => 'rahasia123',
            'role' => Akun::ROLE_KEUANGAN,
        ]);

        $this->actingAs($keuangan)
            ->get(route('mahasiswa.tagihan.index'))
            ->assertForbidden();

        $this->actingAs($keuangan)
            ->post(route('mahasiswa.tagihan.va', ['idTagihan' => 1]))
            ->assertForbidden();
    }

    public function test_mahasiswa_hanya_melihat_semester_dan_tagihan_milik_sendiri(): void
    {
        $akun = $this->mahasiswa('255150400111001');
        $this->mahasiswa('255150400111002');

        $this->tagihan('255150400111001', 'Ganjil 2026/2027');
        $this->tagihan('255150400111002', 'Genap 2027/2028');

        $this->actingAs($akun)
            ->get(route('mahasiswa.tagihan.index', [
                'nim' => '255150400111002',
                'semester' => 'Ganjil 2026/2027',
            ]))
            ->assertOk()
            ->assertSee('255150400111001')
            ->assertDontSee('255150400111002')
            ->assertDontSee('Genap 2027/2028')
            ->assertSee('Rp 5.000.000');

        $this->actingAs($akun)
            ->get(route('mahasiswa.tagihan.index', [
                'semester' => 'Genap 2027/2028',
            ]))
            ->assertOk()
            ->assertSee('Tagihan belum tersedia untuk semester yang dipilih.');
    }

    public function test_mahasiswa_tidak_dapat_meminta_va_tagihan_milik_orang_lain(): void
    {
        $akun = $this->mahasiswa('255150400111001');
        $this->mahasiswa('255150400111002');

        $asing = $this->tagihan('255150400111002');

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('buatVirtualAccount');
        });

        $this->actingAs($akun)
            ->post(route('mahasiswa.tagihan.va', ['idTagihan' => $asing->id_tagihan]), [
                'nim' => '255150400111002',
                'semester' => 'Ganjil 2026/2027',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $asing->id_tagihan,
            'virtual_account' => null,
        ]);
    }

    public function test_mahasiswa_bisa_meminta_va_milik_sendiri_tanpa_input_nim(): void
    {
        config()->set('midtrans.is_production', false);

        $akun = $this->mahasiswa('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($tagihan): void {
            $mock->shouldReceive('buatVirtualAccount')
                ->once()
                ->with((int) $tagihan->id_tagihan)
                ->andReturn(['expiry_time' => '2026-11-24 16:27:01']);
        });

        $this->actingAs($akun)
            ->post(route('mahasiswa.tagihan.va', ['idTagihan' => $tagihan->id_tagihan]))
            ->assertRedirect(route('mahasiswa.tagihan.index', ['semester' => 'Ganjil 2026/2027']))
            ->assertSessionHas('va_success');

        // MidtransService asli bertanggung jawab menyimpan nomor VA.
        // Stub/mock sengaja tidak mengubah data database.
        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Belum Dibayar',
        ]);
    }

    public function test_tagihan_lunas_dan_va_yang_sudah_ada_tidak_dibuat_ulang(): void
    {
        $akun = $this->mahasiswa('255150400111001');
        $lunas = $this->tagihan('255150400111001', 'Ganjil 2026/2027', 'Lunas');
        $sudahVa = $this->tagihan('255150400111001', 'Genap 2026/2027', 'Belum Dibayar', '53113890606570727926740');

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('buatVirtualAccount');
        });

        $this->actingAs($akun)
            ->post(route('mahasiswa.tagihan.va', ['idTagihan' => $lunas->id_tagihan]))
            ->assertSessionHas('va_error');

        $this->actingAs($akun)
            ->post(route('mahasiswa.tagihan.va', ['idTagihan' => $sudahVa->id_tagihan]))
            ->assertSessionHas('va_info');
    }

    public function test_mode_produksi_tidak_menerbitkan_va_dari_modul_sandbox(): void
    {
        config()->set('midtrans.is_production', true);

        $akun = $this->mahasiswa('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('buatVirtualAccount');
        });

        $this->actingAs($akun)
            ->post(route('mahasiswa.tagihan.va', ['idTagihan' => $tagihan->id_tagihan]))
            ->assertSessionHas('va_error', 'Pembuatan Virtual Account hanya tersedia pada Midtrans Sandbox.');
    }

    public function test_mahasiswa_tidak_bisa_menggunakan_akun_tanpa_profil(): void
    {
        $akun = Akun::create([
            'username' => '255150400111001',
            'password' => 'rahasia123',
            'role' => Akun::ROLE_MAHASISWA,
        ]);

        $this->actingAs($akun)
            ->get(route('mahasiswa.tagihan.index'))
            ->assertForbidden();
    }

    public function test_dashboard_dan_uc03_memiliki_navigasi_ke_uc07(): void
    {
        $akun = $this->mahasiswa('255150400111001');
        $this->tagihan('255150400111001');

        $this->actingAs($akun)
            ->get(route('dashboard.mahasiswa'))
            ->assertOk()
            ->assertSee('Buka Pembayaran VA');

        $this->actingAs($akun)
            ->get(route('tagihan.index', ['semester' => 'Ganjil 2026/2027']))
            ->assertOk()
            ->assertSee('Lanjut ke Pembayaran Virtual Account');
    }
}
