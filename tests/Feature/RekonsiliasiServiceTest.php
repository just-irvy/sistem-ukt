<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use App\Models\TransaksiRekonsiliasi;
use App\Services\RekonsiliasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class RekonsiliasiServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Menyiapkan mahasiswa dan tagihan untuk setiap pengujian.
     */
    private function buatTagihan(
        string $status = 'Belum Dibayar'
    ): TagihanUKT {
        Mahasiswa::create([
            'nim' => '255150400111099',
            'nama' => 'Budi Santoso',
        ]);

        return TagihanUKT::create([
            'nim' => '255150400111099',
            'semester' => 'Ganjil 2026/2027',
            'nominal' => 5000000,
            'batas_waktu' => '2026-11-30',
            'status_bayar' => $status,
            'virtual_account' => '8808255150400111099',
        ]);
    }

    /**
     * Menyiapkan data transaksi pengujian.
     */
    private function dataTransaksi(array $tambahan = []): array
    {
        return array_merge([
            'nomor_va' => '8808255150400111099',
            'referensi_bank' => 'TRX-WB-001',
            'nominal_bayar' => 5000000,
            'waktu_transaksi' => '2026-11-12 10:30:00',
        ], $tambahan);
    }

    /**
     * WB-01: VA ditemukan dan nominal sesuai.
     */
    public function test_pembayaran_sesuai_mengubah_tagihan_menjadi_lunas(): void
    {
        $tagihan = $this->buatTagihan();

        $hasil = app(RekonsiliasiService::class)
            ->proses($this->dataTransaksi());

        $this->assertTrue($hasil['accepted']);
        $this->assertSame('Sesuai', $hasil['status']);

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Lunas',
        ]);

        $this->assertDatabaseHas('transaksi_rekonsiliasi', [
            'referensi_bank' => 'TRX-WB-001',
            'status_flag' => 'Sesuai',
        ]);
    }

    /**
     * WB-02: Nomor VA tidak ditemukan.
     */
    public function test_va_tidak_ditemukan_menandai_transaksi_tidak_sesuai(): void
    {
        $this->buatTagihan();

        $hasil = app(RekonsiliasiService::class)
            ->proses($this->dataTransaksi([
                'nomor_va' => '99999999999999999999',
            ]));

        $this->assertTrue($hasil['accepted']);
        $this->assertSame('Tidak Sesuai', $hasil['status']);
        $this->assertNull($hasil['id_tagihan']);

        $this->assertDatabaseHas('transaksi_rekonsiliasi', [
            'referensi_bank' => 'TRX-WB-001',
            'id_tagihan' => null,
            'status_flag' => 'Tidak Sesuai',
        ]);
    }

    /**
     * WB-03: Nominal pembayaran kurang.
     */
    public function test_nominal_kurang_menandai_transaksi_tidak_sesuai(): void
    {
        $tagihan = $this->buatTagihan();

        $hasil = app(RekonsiliasiService::class)
            ->proses($this->dataTransaksi([
                'nominal_bayar' => 4500000,
            ]));

        $this->assertTrue($hasil['accepted']);
        $this->assertSame('Tidak Sesuai', $hasil['status']);

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Belum Dibayar',
        ]);
    }

    /**
     * WB-04: Nominal pembayaran berlebih.
     */
    public function test_nominal_berlebih_menandai_transaksi_tidak_sesuai(): void
    {
        $tagihan = $this->buatTagihan();

        $hasil = app(RekonsiliasiService::class)
            ->proses($this->dataTransaksi([
                'nominal_bayar' => 5500000,
            ]));

        $this->assertTrue($hasil['accepted']);
        $this->assertSame('Tidak Sesuai', $hasil['status']);

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Belum Dibayar',
        ]);
    }

    /**
     * WB-05: Tagihan sudah berstatus Lunas.
     */
    public function test_tagihan_yang_sudah_lunas_tidak_diproses_sebagai_pembayaran_baru(): void
    {
        $tagihan = $this->buatTagihan('Lunas');

        $hasil = app(RekonsiliasiService::class)
            ->proses($this->dataTransaksi());

        $this->assertTrue($hasil['accepted']);
        $this->assertSame('Tidak Sesuai', $hasil['status']);

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Lunas',
        ]);
    }

    /**
     * WB-06: Referensi transaksi sudah pernah diproses.
     */
    public function test_referensi_transaksi_duplikat_ditolak(): void
    {
        $this->buatTagihan();

        $service = app(RekonsiliasiService::class);

        // Pemrosesan transaksi pertama.
        $hasilPertama = $service->proses(
            $this->dataTransaksi()
        );

        // Pemrosesan ulang dengan referensi yang sama.
        $hasilKedua = $service->proses(
            $this->dataTransaksi()
        );

        $this->assertTrue($hasilPertama['accepted']);

        $this->assertFalse($hasilKedua['accepted']);
        $this->assertSame('Duplikat', $hasilKedua['status']);

        // Memastikan transaksi hanya tersimpan satu kali.
        $this->assertSame(
            1,
            TransaksiRekonsiliasi::count()
        );
    }
    /**
     * WB-07: Format nominal pembayaran tidak valid.
     */
    public function test_format_nominal_tidak_valid_menghasilkan_exception(): void
    {
        $this->buatTagihan();

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Format nominal pembayaran tidak valid.'
        );

        app(RekonsiliasiService::class)->proses(
            $this->dataTransaksi([
                'nominal_bayar' => 'abc',
            ])
        );
    }
}
