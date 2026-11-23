<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Mahasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenetapanTagihanOtorisasiTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $role): Akun
    {
        return Akun::create([
            'username' => 'uji-'.str_replace(' ', '-', strtolower($role)),
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function tagihanBaru(): array
    {
        Mahasiswa::create([
            'nim' => '255150400111099',
            'nama' => 'Mahasiswa Pengujian',
        ]);

        return [
            'nim' => '255150400111099',
            'semester' => 'Ganjil 2030/2031',
            'nominal' => '5000000',
            'batas_waktu' => now()->addYear()->toDateString(),
        ];
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_penetapan_tagihan(): void
    {
        $this->get(route('keuangan.penetapan-tagihan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_bisa_menetapkan_tagihan(): void
    {
        $data = $this->tagihanBaru();

        $this->post(route('keuangan.penetapan-tagihan.store'), $data)
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('tagihan_ukt', 0);
    }

    public function test_mahasiswa_tidak_boleh_melihat_daftar_mahasiswa_untuk_penetapan(): void
    {
        $this->actingAs($this->akun(Akun::ROLE_MAHASISWA))
            ->get(route('keuangan.penetapan-tagihan.index'))
            ->assertForbidden();
    }

    public function test_mahasiswa_tidak_boleh_menyimpan_penetapan_tagihan(): void
    {
        $data = $this->tagihanBaru();

        $this->actingAs($this->akun(Akun::ROLE_MAHASISWA))
            ->post(route('keuangan.penetapan-tagihan.store'), $data)
            ->assertForbidden();

        $this->assertDatabaseCount('tagihan_ukt', 0);
    }

    public function test_verifikator_tidak_boleh_mengakses_penetapan_tagihan(): void
    {
        $akun = $this->akun(Akun::ROLE_VERIFIKATOR);

        $this->actingAs($akun)
            ->get(route('keuangan.penetapan-tagihan.index'))
            ->assertForbidden();

        $this->actingAs($akun)
            ->post(route('keuangan.penetapan-tagihan.store'), $this->tagihanBaru())
            ->assertForbidden();

        $this->assertDatabaseCount('tagihan_ukt', 0);
    }

    public function test_bagian_keuangan_boleh_melihat_dan_menetapkan_tagihan(): void
    {
        $data = $this->tagihanBaru();
        $akun = $this->akun(Akun::ROLE_KEUANGAN);

        $this->actingAs($akun)
            ->get(route('keuangan.penetapan-tagihan.index'))
            ->assertOk()
            ->assertSee('Penetapan Tagihan UKT')
            ->assertSee('Mahasiswa Pengujian');

        $this->actingAs($akun)
            ->post(route('keuangan.penetapan-tagihan.store'), $data)
            ->assertRedirect(route('keuangan.penetapan-tagihan.index', [
                'semester' => 'Ganjil 2030/2031',
            ]));

        $this->assertDatabaseHas('tagihan_ukt', [
            'nim' => '255150400111099',
            'semester' => 'Ganjil 2030/2031',
            'nominal' => '5000000.00',
            'status_bayar' => 'Belum Dibayar',
        ]);
    }
}
