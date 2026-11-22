<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PengajuanUKT;
use App\Models\TagihanUKT;
use App\Services\PengajuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * UC-04 Ajukan Penyesuaian UKT | PS-03
 */
class PengajuanServiceTest extends TestCase
{
    use RefreshDatabase;

    private const NIM = '255150400111029';

    private const SEMESTER = '2026/2027 Ganjil';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Akun::createAkun([
            'nim' => self::NIM,
            'nama' => 'Fahim Humam Mahdi',
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ]);

        TagihanUKT::create([
            'nim' => self::NIM,
            'semester' => self::SEMESTER,
            'nominal' => 5000000,
            'batas_waktu' => '2026-12-31',
            'status_bayar' => 'Belum Dibayar',
        ]);
    }

    private function service(): PengajuanService
    {
        return app(PengajuanService::class);
    }

    private function pdf(int $kb = 100, string $nama = 'slip.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($nama, $kb, 'application/pdf');
    }

    private function kirim(?string $alasan = 'Penghasilan orang tua menurun.', ?UploadedFile $file = null): PengajuanUKT
    {
        return $this->service()->submitPengajuan(
            self::NIM,
            self::SEMESTER,
            $alasan,
            $file ?? $this->pdf()
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

        $this->assertDatabaseCount('pengajuan_ukt', 0);
    }

    public function test_pengajuan_valid_tersimpan_dengan_status_menunggu(): void
    {
        $pengajuan = $this->kirim();

        $this->assertSame(PengajuanUKT::STATUS_MENUNGGU, $pengajuan->fresh()->status_verifikasi);
        $this->assertDatabaseHas('pengajuan_ukt', [
            'nim' => self::NIM,
            'semester' => self::SEMESTER,
            'alasan' => 'Penghasilan orang tua menurun.',
        ]);
        Storage::disk('local')->assertExists($pengajuan->dokumen_pendukung);
    }

    public function test_alasan_kosong_ditolak_dan_tidak_menyimpan_apa_pun(): void
    {
        $this->assertGagal(fn () => $this->kirim(''), 'alasan');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_dokumen_kosong_ditolak(): void
    {
        $this->assertGagal(
            fn () => $this->service()->submitPengajuan(self::NIM, self::SEMESTER, 'Alasan', null),
            'dokumen'
        );
    }

    public function test_format_dokumen_selain_pdf_jpg_ditolak(): void
    {
        $docx = UploadedFile::fake()->create(
            'surat.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $this->assertGagal(fn () => $this->kirim('Alasan', $docx), 'dokumen');
    }

    public function test_dokumen_lebih_dari_2_mb_ditolak(): void
    {
        $this->assertGagal(fn () => $this->kirim('Alasan', $this->pdf(3000)), 'dokumen');
    }

    public function test_nim_tidak_terdaftar_ditolak(): void
    {
        $this->assertGagal(
            fn () => $this->service()->submitPengajuan('000000000000000', self::SEMESTER, 'Alasan', $this->pdf()),
            'nim'
        );
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_semester_tanpa_tagihan_ditolak(): void
    {
        $this->assertGagal(
            fn () => $this->service()->submitPengajuan(self::NIM, '2099/2100 Genap', 'Alasan', $this->pdf()),
            'semester'
        );
    }

    public function test_tagihan_lunas_tidak_bisa_diajukan(): void
    {
        TagihanUKT::where('nim', self::NIM)->update(['status_bayar' => 'Lunas']);

        $this->assertGagal(fn () => $this->kirim(), 'semester');
    }

    public function test_pengajuan_kedua_saat_masih_menunggu_ditolak(): void
    {
        $this->kirim('Alasan 1');

        try {
            $this->kirim('Alasan 2');
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('semester', $e->errors());
        }

        $this->assertDatabaseCount('pengajuan_ukt', 1);
    }

    public function test_pengajuan_baru_boleh_setelah_yang_lama_diputuskan(): void
    {
        $lama = $this->kirim('Alasan 1');
        $lama->update(['status_verifikasi' => PengajuanUKT::STATUS_DITOLAK]);

        $baru = $this->kirim('Alasan 2');

        $this->assertNotSame($lama->id_pengajuan, $baru->id_pengajuan);
        $this->assertDatabaseCount('pengajuan_ukt', 2);
    }
}
