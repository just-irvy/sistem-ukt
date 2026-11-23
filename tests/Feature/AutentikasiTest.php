<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    private function dataRegistrasi(array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Hasannain Thauri',
            'nim' => '255150400111031',
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ], $tambahan);
    }

    private function buatMahasiswaDenganAkun(
        string $nim = '255150400111031'
    ): Akun {
        return Akun::createAkun($this->dataRegistrasi(['nim' => $nim]));
    }

    // ---------- UC-01 Registrasi (PS-01) ----------

    public function test_registrasi_berhasil_membuat_akun_dan_mahasiswa(): void
    {
        $response = $this->post(
            route('registrasi.submit'),
            $this->dataRegistrasi()
        );

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registrasi Berhasil');

        $akun = Akun::where('username', '255150400111031')->first();
        $this->assertNotNull($akun);
        $this->assertSame(Akun::ROLE_MAHASISWA, $akun->role);
        $this->assertNotSame('rahasia123', $akun->password);
        $this->assertTrue(Hash::check('rahasia123', $akun->password));

        $this->assertDatabaseHas('mahasiswa', [
            'nim' => '255150400111031',
            'nama' => 'Hasannain Thauri',
            'id_akun' => $akun->id_akun,
        ]);
    }

    public function test_registrasi_dengan_data_kosong_ditolak(): void
    {
        $response = $this->from(route('registrasi'))->post(
            route('registrasi.submit'),
            $this->dataRegistrasi(['prodi' => ''])
        );

        $response->assertRedirect(route('registrasi'));
        $response->assertSessionHasErrors(['registrasi' => 'Data tidak lengkap']);
        $this->assertDatabaseCount('akun', 0);
        $this->assertDatabaseCount('mahasiswa', 0);
    }

    public function test_registrasi_dengan_nim_terdaftar_ditolak(): void
    {
        $this->buatMahasiswaDenganAkun();

        $response = $this->from(route('registrasi'))->post(
            route('registrasi.submit'),
            $this->dataRegistrasi(['nama' => 'Orang Lain'])
        );

        $response->assertSessionHasErrors(['registrasi' => 'NIM sudah terdaftar']);
        $this->assertDatabaseCount('akun', 1);
        $this->assertDatabaseCount('mahasiswa', 1);
    }

    public function test_registrasi_menolak_format_nim_dan_kata_sandi_pendek(): void
    {
        $response = $this->from(route('registrasi'))->post(
            route('registrasi.submit'),
            $this->dataRegistrasi(['nim' => 'ABC123', 'password' => 'pendek'])
        );

        $response->assertSessionHasErrors(['nim', 'password']);
        $this->assertDatabaseCount('akun', 0);
    }

    public function test_form_registrasi_dapat_dibuka(): void
    {
        $this->get(route('registrasi'))
            ->assertOk()
            ->assertSee('Registrasi Akun');
    }

    // ---------- UC-02 Login (PS-02) ----------

    public function test_login_mahasiswa_diarahkan_ke_dashboard_mahasiswa(): void
    {
        $this->buatMahasiswaDenganAkun();

        $response = $this->post(route('login.submit'), [
            'username' => '255150400111031',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('dashboard.mahasiswa'));
        $this->assertAuthenticated();
    }

    public function test_login_bagian_keuangan_diarahkan_ke_dashboard_keuangan(): void
    {
        Akun::create([
            'username' => '198001012005011001',
            'password' => 'rahasia123',
            'role' => Akun::ROLE_KEUANGAN,
        ]);

        $this->post(route('login.submit'), [
            'username' => '198001012005011001',
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard.keuangan'));
    }

    public function test_login_dengan_password_salah_ditolak(): void
    {
        $this->buatMahasiswaDenganAkun();

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'username' => '255150400111031',
            'password' => 'salah-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['login' => 'Username atau Password salah']);
        $this->assertGuest();
    }

    public function test_login_dengan_username_tidak_terdaftar_ditolak(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'username' => '999999999999999',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors(['login' => 'Username atau Password salah']);
        $this->assertGuest();
    }

    public function test_login_dengan_role_tidak_dikenali_ditolak(): void
    {
        Akun::create([
            'username' => 'aneh',
            'password' => 'rahasia123',
            'role' => 'Tamu',
        ]);

        $this->from(route('login'))->post(route('login.submit'), [
            'username' => 'aneh',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_dashboard_tidak_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('dashboard.mahasiswa'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_menolak_role_yang_tidak_sesuai(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();

        $this->actingAs($akun)
            ->get(route('dashboard.keuangan'))
            ->assertForbidden();
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();

        $this->actingAs($akun)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // ---------- UC-03 Lihat Tagihan Semester (PS-07) ----------

    private function buatTagihan(string $nim, string $semester): TagihanUKT
    {
        return TagihanUKT::create([
            'nim' => $nim,
            'semester' => $semester,
            'nominal' => 5000000,
            'batas_waktu' => '2026-11-30',
            'status_bayar' => 'Belum Dibayar',
            'virtual_account' => null,
        ]);
    }

    public function test_mahasiswa_dapat_melihat_tagihan_semester(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();
        $this->buatTagihan('255150400111031', 'Ganjil 2026/2027');

        $this->actingAs($akun)
            ->getJson(route('tagihan.semester', ['semester' => 'Ganjil 2026/2027']))
            ->assertOk()
            ->assertJsonPath('data.nominal', '5000000.00')
            ->assertJsonPath('data.batas_waktu', '2026-11-30')
            ->assertJsonPath('data.status_bayar', 'Belum Dibayar');
    }

    public function test_tagihan_belum_tersedia_mengembalikan_pesan(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();

        $this->actingAs($akun)
            ->getJson(route('tagihan.semester', ['semester' => 'Genap 2026/2027']))
            ->assertNotFound()
            ->assertJsonPath('message', 'Tagihan belum tersedia');
    }

    public function test_mahasiswa_tidak_melihat_tagihan_mahasiswa_lain(): void
    {
        $akun = $this->buatMahasiswaDenganAkun('255150400111031');
        $this->buatMahasiswaDenganAkun('255150400111026');
        $this->buatTagihan('255150400111026', 'Ganjil 2026/2027');

        $this->actingAs($akun)
            ->getJson(route('tagihan.semester', ['semester' => 'Ganjil 2026/2027']))
            ->assertNotFound();
    }

    public function test_tagihan_membutuhkan_login_dan_parameter_semester(): void
    {
        $this->getJson(route('tagihan.semester', ['semester' => 'Ganjil 2026/2027']))
            ->assertUnauthorized();

        $akun = $this->buatMahasiswaDenganAkun();
        $this->actingAs($akun)
            ->getJson(route('tagihan.semester'))
            ->assertStatus(422);
    }

    public function test_non_mahasiswa_tidak_dapat_melihat_tagihan(): void
    {
        $akun = Akun::create([
            'username' => '198001012005011001',
            'password' => 'rahasia123',
            'role' => Akun::ROLE_KEUANGAN,
        ]);

        $this->actingAs($akun)
            ->getJson(route('tagihan.semester', ['semester' => 'Ganjil 2026/2027']))
            ->assertForbidden();
    }

    // ---------- UC-03 UI-03 Halaman Tagihan Semester ----------

    public function test_halaman_tagihan_menampilkan_dropdown_semester(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();

        $this->actingAs($akun)
            ->get(route('tagihan.index'))
            ->assertOk()
            ->assertSee('Tagihan Semester')
            ->assertSee('Cari Tagihan')
            ->assertSee('Tampilkan Tagihan');
    }

    public function test_halaman_tagihan_menampilkan_nominal_batas_waktu_dan_status(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();
        $this->buatTagihan('255150400111031', 'Ganjil 2026/2027');

        $this->actingAs($akun)
            ->get(route('tagihan.index', ['semester' => 'Ganjil 2026/2027']))
            ->assertOk()
            ->assertSee('Rp 5.000.000')
            ->assertSee('30-11-2026')
            ->assertSee('Belum Dibayar');
    }

    public function test_halaman_tagihan_menampilkan_pesan_jika_belum_tersedia(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();

        $this->actingAs($akun)
            ->get(route('tagihan.index', ['semester' => 'Genap 2026/2027']))
            ->assertOk()
            ->assertSee('Tagihan belum tersedia');
    }

    public function test_halaman_tagihan_tidak_menampilkan_tagihan_mahasiswa_lain(): void
    {
        $akun = $this->buatMahasiswaDenganAkun('255150400111031');
        $this->buatMahasiswaDenganAkun('255150400111026');
        $this->buatTagihan('255150400111026', 'Ganjil 2026/2027');

        $this->actingAs($akun)
            ->get(route('tagihan.index', ['semester' => 'Ganjil 2026/2027']))
            ->assertOk()
            ->assertSee('Tagihan belum tersedia')
            ->assertDontSee('Rp 5.000.000');
    }

    public function test_halaman_tagihan_butuh_login_dan_hanya_untuk_mahasiswa(): void
    {
        $this->get(route('tagihan.index'))
            ->assertRedirect(route('login'));

        $keuangan = Akun::create([
            'username' => '198001012005011001',
            'password' => 'rahasia123',
            'role' => Akun::ROLE_KEUANGAN,
        ]);

        $this->actingAs($keuangan)
            ->get(route('tagihan.index'))
            ->assertForbidden();
    }

    public function test_dashboard_mahasiswa_memiliki_menu_tagihan(): void
    {
        $akun = $this->buatMahasiswaDenganAkun();

        $this->actingAs($akun)
            ->get(route('dashboard.mahasiswa'))
            ->assertOk()
            ->assertSee('Lihat Tagihan Semester');
    }

    public function test_daftar_semester_memuat_semester_yang_sudah_punya_tagihan(): void
    {
        $this->buatMahasiswaDenganAkun();
        $this->buatTagihan('255150400111031', 'Ganjil 1999/2000');

        $daftar = TagihanUKT::getDaftarSemester('255150400111031');

        $this->assertContains('Ganjil 1999/2000', $daftar);
        $this->assertSame(count($daftar), count(array_unique($daftar)));
    }

    public function test_form_login_dapat_dibuka(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke Sistem')
            ->assertSee('Username / NIM / NIP');
    }
}
