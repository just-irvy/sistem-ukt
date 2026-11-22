<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use App\Models\Verifikator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur HTTP UC-04 (Mahasiswa) dan UC-05 (Verifikator): UI-04 dan UI-05.
 */
class PengajuanVerifikasiHttpTest extends TestCase
{
    use RefreshDatabase;

    private const NIM = '255150400111029';

    private const SEMESTER = '2026/2027 Ganjil';

    private Akun $mahasiswa;

    private Akun $verifikator;

    private TagihanUKT $tagihan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->mahasiswa = Akun::createAkun([
            'nim' => self::NIM,
            'nama' => 'Fahim Humam Mahdi',
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ]);

        $this->verifikator = Akun::create([
            'username' => '198502022010012002',
            'password' => 'password123',
            'role' => Akun::ROLE_VERIFIKATOR,
        ]);
        Verifikator::create([
            'nip' => '198502022010012002',
            'nama' => 'Verifikator UKT',
            'id_akun' => $this->verifikator->id_akun,
        ]);

        $this->tagihan = TagihanUKT::create([
            'nim' => self::NIM,
            'semester' => self::SEMESTER,
            'nominal' => 5000000,
            'batas_waktu' => '2026-12-31',
            'status_bayar' => 'Belum Dibayar',
        ]);
    }

    private function kirimPengajuan(array $tambahan = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->mahasiswa)->post(route('pengajuan.store'), array_merge([
            'semester' => self::SEMESTER,
            'alasan' => 'Penghasilan orang tua menurun.',
            'dokumen' => UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf'),
        ], $tambahan));
    }

    // ---------- Hak akses ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('pengajuan.create'))->assertRedirect(route('login'));
        $this->get(route('verifikasi.index'))->assertRedirect(route('login'));
    }

    public function test_verifikator_tidak_dapat_membuka_form_pengajuan(): void
    {
        $this->actingAs($this->verifikator)->get(route('pengajuan.create'))->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_membuka_halaman_verifikasi(): void
    {
        $pengajuan = PengajuanUKT::savePengajuan(self::NIM, self::SEMESTER, 'x', 'pengajuan-ukt/a.pdf');

        $this->actingAs($this->mahasiswa)->get(route('verifikasi.index'))->assertForbidden();
        $this->actingAs($this->mahasiswa)->get(route('verifikasi.show', $pengajuan->id_pengajuan))->assertForbidden();
        $this->actingAs($this->mahasiswa)->get(route('verifikasi.dokumen', $pengajuan->id_pengajuan))->assertForbidden();
        $this->actingAs($this->mahasiswa)->post(route('verifikasi.proses', $pengajuan->id_pengajuan), [
            'keputusan' => PengajuanUKT::STATUS_DISETUJUI,
            'nominal_baru' => 1,
        ])->assertForbidden();

        $this->assertTrue($pengajuan->fresh()->menunggu());
    }

    // ---------- UC-04 / UI-04 ----------

    public function test_form_pengajuan_menampilkan_semester_yang_belum_lunas(): void
    {
        TagihanUKT::create([
            'nim' => self::NIM, 'semester' => '2025/2026 Genap', 'nominal' => 4000000,
            'batas_waktu' => '2026-06-30', 'status_bayar' => 'Lunas',
        ]);

        $this->actingAs($this->mahasiswa)->get(route('pengajuan.create'))
            ->assertOk()
            ->assertSee(self::SEMESTER)
            ->assertDontSee('2025/2026 Genap')
            ->assertSee('Kirim Pengajuan');
    }

    public function test_mahasiswa_berhasil_mengirim_pengajuan(): void
    {
        $this->kirimPengajuan()
            ->assertRedirect(route('pengajuan.create'))
            ->assertSessionHas('status', 'Pengajuan Sukses Terkirim');

        $this->assertDatabaseHas('pengajuan_ukt', [
            'nim' => self::NIM,
            'semester' => self::SEMESTER,
            'status_verifikasi' => PengajuanUKT::STATUS_MENUNGGU,
        ]);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_pengajuan_tanpa_alasan_atau_dokumen_salah_ditolak_dengan_pesan(): void
    {
        $this->kirimPengajuan(['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->kirimPengajuan(['dokumen' => UploadedFile::fake()->create('a.docx', 10)])->assertSessionHasErrors('dokumen');
        $this->kirimPengajuan(['dokumen' => UploadedFile::fake()->create('besar.pdf', 3000, 'application/pdf')])->assertSessionHasErrors('dokumen');

        $this->assertDatabaseCount('pengajuan_ukt', 0);
    }

    public function test_riwayat_hanya_menampilkan_pengajuan_milik_sendiri(): void
    {
        $lain = Akun::createAkun([
            'nim' => '255150400111099', 'nama' => 'Orang Lain', 'no_telp' => '0811',
            'fakultas' => 'Ilmu Komputer', 'prodi' => 'Sistem Informasi', 'password' => 'rahasia123',
        ]);
        PengajuanUKT::savePengajuan('255150400111099', '2026/2027 Ganjil', 'ALASAN-ORANG-LAIN', 'pengajuan-ukt/x.pdf');
        PengajuanUKT::savePengajuan(self::NIM, self::SEMESTER, 'ALASAN-SAYA', 'pengajuan-ukt/y.pdf');

        $this->actingAs($this->mahasiswa)->get(route('pengajuan.create'))
            ->assertSee('ALASAN-SAYA')
            ->assertDontSee('ALASAN-ORANG-LAIN');
    }

    // ---------- UC-05 / UI-05 ----------

    public function test_verifikator_melihat_daftar_dan_detail_pengajuan(): void
    {
        $this->kirimPengajuan();
        $pengajuan = PengajuanUKT::first();

        $this->actingAs($this->verifikator)->get(route('verifikasi.index'))
            ->assertOk()
            ->assertSee('Fahim Humam Mahdi')
            ->assertSee('Menunggu Verifikasi');

        $this->actingAs($this->verifikator)->get(route('verifikasi.show', $pengajuan->id_pengajuan))
            ->assertOk()
            ->assertSee('Penghasilan orang tua menurun.')
            ->assertSee('Lihat dokumen')
            ->assertSee('Setujui')
            ->assertSee('Tolak');
    }

    public function test_verifikator_dapat_membuka_dokumen_pendukung(): void
    {
        $this->kirimPengajuan();
        $pengajuan = PengajuanUKT::first();

        $this->actingAs($this->verifikator)
            ->get(route('verifikasi.dokumen', $pengajuan->id_pengajuan))
            ->assertOk();
    }

    public function test_dokumen_yang_hilang_menghasilkan_404(): void
    {
        $pengajuan = PengajuanUKT::savePengajuan(self::NIM, self::SEMESTER, 'x', 'pengajuan-ukt/tidak-ada.pdf');

        $this->actingAs($this->verifikator)
            ->get(route('verifikasi.dokumen', $pengajuan->id_pengajuan))
            ->assertNotFound();
    }

    public function test_verifikator_menyetujui_pengajuan_dan_nominal_berubah(): void
    {
        $this->kirimPengajuan();
        $pengajuan = PengajuanUKT::first();

        $this->actingAs($this->verifikator)
            ->post(route('verifikasi.proses', $pengajuan->id_pengajuan), [
                'keputusan' => PengajuanUKT::STATUS_DISETUJUI,
                'nominal_baru' => 3000000,
                'catatan' => 'Dokumen valid.',
            ])
            ->assertRedirect(route('verifikasi.index'))
            ->assertSessionHas('status', 'Verifikasi Selesai');

        $this->assertSame(PengajuanUKT::STATUS_DISETUJUI, $pengajuan->fresh()->status_verifikasi);
        $this->assertEquals(3000000, $this->tagihan->fresh()->nominal);
    }

    public function test_verifikator_menolak_dengan_catatan_dan_nominal_tetap(): void
    {
        $this->kirimPengajuan();
        $pengajuan = PengajuanUKT::first();

        $this->actingAs($this->verifikator)
            ->post(route('verifikasi.proses', $pengajuan->id_pengajuan), [
                'keputusan' => PengajuanUKT::STATUS_DITOLAK,
                'catatan' => 'Dokumen tidak valid.',
            ])
            ->assertRedirect(route('verifikasi.index'));

        $this->assertSame(PengajuanUKT::STATUS_DITOLAK, $pengajuan->fresh()->status_verifikasi);
        $this->assertEquals(5000000, $this->tagihan->fresh()->nominal);
    }

    public function test_penolakan_tanpa_catatan_menampilkan_error_dan_status_tetap(): void
    {
        $this->kirimPengajuan();
        $pengajuan = PengajuanUKT::first();

        $this->actingAs($this->verifikator)
            ->post(route('verifikasi.proses', $pengajuan->id_pengajuan), [
                'keputusan' => PengajuanUKT::STATUS_DITOLAK,
                'catatan' => '',
            ])
            ->assertSessionHasErrors('catatan');

        $this->assertTrue($pengajuan->fresh()->menunggu());
    }

    public function test_mahasiswa_melihat_hasil_verifikasi_di_riwayat(): void
    {
        $this->kirimPengajuan();
        $pengajuan = PengajuanUKT::first();

        $this->actingAs($this->verifikator)->post(route('verifikasi.proses', $pengajuan->id_pengajuan), [
            'keputusan' => PengajuanUKT::STATUS_DITOLAK,
            'catatan' => 'Slip gaji tidak terbaca.',
        ]);

        $this->actingAs($this->mahasiswa)->get(route('pengajuan.create'))
            ->assertSee('Ditolak')
            ->assertSee('Slip gaji tidak terbaca.');
    }

    public function test_dashboard_menampilkan_menu_sesuai_role(): void
    {
        $this->actingAs($this->mahasiswa)->get(route('dashboard.mahasiswa'))
            ->assertSee(route('pengajuan.create'), false);

        $this->actingAs($this->verifikator)->get(route('dashboard.verifikator'))
            ->assertSee(route('verifikasi.index'), false);
    }
}
