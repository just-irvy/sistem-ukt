<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use App\Services\VerifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * UC-05 Verifikasi Pengajuan UKT | PS-04
 */
class VerifikasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private const NIM = '255150400111029';

    private const SEMESTER = '2026/2027 Ganjil';

    private TagihanUKT $tagihan;

    private PengajuanUKT $pengajuan;

    protected function setUp(): void
    {
        parent::setUp();

        Akun::createAkun([
            'nim' => self::NIM,
            'nama' => 'Fahim Humam Mahdi',
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ]);

        $this->tagihan = TagihanUKT::create([
            'nim' => self::NIM,
            'semester' => self::SEMESTER,
            'nominal' => 5000000,
            'batas_waktu' => '2026-12-31',
            'status_bayar' => 'Belum Dibayar',
        ]);

        $this->pengajuan = PengajuanUKT::savePengajuan(
            self::NIM, self::SEMESTER, 'Penghasilan menurun.', 'pengajuan-ukt/slip.pdf'
        );
    }

    private function proses(
        string $keputusan,
        ?string $catatan = null,
        $nominal = null,
        ?int $idTagihan = -1,
        ?int $idPengajuan = null
    ): PengajuanUKT {
        return app(VerifikasiService::class)->prosesVerifikasi(
            $idPengajuan ?? $this->pengajuan->id_pengajuan,
            $keputusan,
            $catatan,
            $idTagihan === -1 ? $this->tagihan->id_tagihan : $idTagihan,
            $nominal
        );
    }

    private function assertGagal(callable $aksi, string $kolom): void
    {
        try {
            $aksi();
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($kolom, $e->errors());
        }
    }

    public function test_persetujuan_mengubah_status_dan_nominal(): void
    {
        $hasil = $this->proses(PengajuanUKT::STATUS_DISETUJUI, 'Dokumen valid.', 3000000);

        $this->assertSame(PengajuanUKT::STATUS_DISETUJUI, $hasil->status_verifikasi);
        $this->assertSame('Dokumen valid.', $hasil->catatan_verifikator);
        $this->assertEquals(3000000, $this->tagihan->fresh()->nominal);
    }

    public function test_persetujuan_melepas_virtual_account_lama(): void
    {
        $this->tagihan->update([
            'virtual_account' => '8800112233445566',
            'midtrans_order_id' => 'UKT-1',
            'va_expires_at' => '2026-12-01 10:00:00',
        ]);

        $this->proses(PengajuanUKT::STATUS_DISETUJUI, null, 3000000);

        $tagihan = $this->tagihan->fresh();
        $this->assertNull($tagihan->virtual_account);
        $this->assertNull($tagihan->midtrans_order_id);
        $this->assertNull($tagihan->va_expires_at);
    }

    public function test_penolakan_menyimpan_catatan_dan_tidak_mengubah_nominal(): void
    {
        $hasil = $this->proses(PengajuanUKT::STATUS_DITOLAK, 'Dokumen tidak valid.');

        $this->assertSame(PengajuanUKT::STATUS_DITOLAK, $hasil->status_verifikasi);
        $this->assertEquals(5000000, $this->tagihan->fresh()->nominal);
    }

    public function test_penolakan_tanpa_catatan_ditolak(): void
    {
        $this->assertGagal(fn () => $this->proses(PengajuanUKT::STATUS_DITOLAK, '   '), 'catatan');
        $this->assertTrue($this->pengajuan->fresh()->menunggu());
    }

    public function test_persetujuan_tanpa_nominal_valid_ditolak(): void
    {
        foreach ([null, '', 'abc', 0, -5, '99999999999'] as $nominal) {
            $this->assertGagal(
                fn () => $this->proses(PengajuanUKT::STATUS_DISETUJUI, null, $nominal),
                'nominal_baru'
            );
        }

        $this->assertTrue($this->pengajuan->fresh()->menunggu());
        $this->assertEquals(5000000, $this->tagihan->fresh()->nominal);
    }

    public function test_keputusan_tidak_dikenal_ditolak(): void
    {
        $this->assertGagal(fn () => $this->proses('Mungkin', 'x', 1000), 'keputusan');
    }

    public function test_pengajuan_yang_sudah_diputuskan_tidak_bisa_diputuskan_lagi(): void
    {
        $this->proses(PengajuanUKT::STATUS_DITOLAK, 'Tidak valid.');

        $this->assertGagal(
            fn () => $this->proses(PengajuanUKT::STATUS_DISETUJUI, null, 1000000),
            'pengajuan'
        );
        $this->assertEquals(5000000, $this->tagihan->fresh()->nominal);
    }

    public function test_pengajuan_tidak_ditemukan_ditolak(): void
    {
        $this->assertGagal(
            fn () => $this->proses(PengajuanUKT::STATUS_DITOLAK, 'x', null, -1, 9999),
            'pengajuan'
        );
    }

    public function test_tagihan_lunas_tidak_bisa_diubah_nominalnya(): void
    {
        $this->tagihan->update(['status_bayar' => 'Lunas']);

        $this->assertGagal(
            fn () => $this->proses(PengajuanUKT::STATUS_DISETUJUI, null, 1000000),
            'tagihan'
        );
        $this->assertTrue($this->pengajuan->fresh()->menunggu());
    }

    public function test_tagihan_milik_semester_atau_mahasiswa_lain_ditolak(): void
    {
        $lain = TagihanUKT::create([
            'nim' => self::NIM,
            'semester' => '2025/2026 Genap',
            'nominal' => 4000000,
            'batas_waktu' => '2026-06-30',
            'status_bayar' => 'Belum Dibayar',
        ]);

        $this->assertGagal(
            fn () => $this->proses(PengajuanUKT::STATUS_DISETUJUI, null, 1000000, $lain->id_tagihan),
            'tagihan'
        );
        $this->assertGagal(
            fn () => $this->proses(PengajuanUKT::STATUS_DISETUJUI, null, 1000000, null),
            'tagihan'
        );
        $this->assertEquals(4000000, $lain->fresh()->nominal);
    }
}
