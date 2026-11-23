<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\TagihanUKT;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class TagihanMahasiswaVaRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $nim): Akun
    {
        return Akun::createAkun([
            'nim' => $nim,
            'nama' => 'Mahasiswa Uji',
            'no_telp' => '081234567890',
            'fakultas' => 'Ilmu Komputer',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
        ]);
    }

    private function tagihan(string $nim): TagihanUKT
    {
        return TagihanUKT::create([
            'nim' => $nim,
            'semester' => 'Genap 2027/2028',
            'nominal' => 5000000,
            'batas_waktu' => '2028-05-31',
            'status_bayar' => 'Belum Dibayar',
            'virtual_account' => '53113794609664399508145',
            'midtrans_order_id' => 'UKT-OLD-1',
            'va_expires_at' => '2026-11-21 08:24:25',
        ]);
    }

    private function statusApi(string $transactionStatus = 'expire', string $va = '53113794609664399508145'): void
    {
        Http::fake([
            'api.sandbox.midtrans.com/*' => Http::response([
                'order_id' => 'UKT-OLD-1',
                'transaction_status' => $transactionStatus,
                'payment_type' => 'bank_transfer',
                'merchant_id' => 'M-TEST-UC07',
                'gross_amount' => '5000000.00',
                'va_numbers' => [['bank' => 'bca', 'va_number' => $va]],
            ], 200),
        ]);
    }

    private function setupSandbox(): void
    {
        config()->set('midtrans.is_production', false);
        config()->set('midtrans.server_key', 'FAKE-TEST-KEY');
        config()->set('midtrans.merchant_id', 'M-TEST-UC07');
    }

    public function test_tamu_tidak_bisa_memperbarui_va(): void
    {
        $this->post(route('mahasiswa.tagihan.va.renew', ['idTagihan' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_mahasiswa_tidak_bisa_memperbarui_va_milik_orang_lain(): void
    {
        $this->actingAs($this->akun('255150400111001'));
        $this->akun('255150400111002');
        $tagihan = $this->tagihan('255150400111002');

        $this->post(route('mahasiswa.tagihan.va.renew', ['idTagihan' => $tagihan->id_tagihan]))
            ->assertNotFound();

        $this->assertDatabaseCount('riwayat_virtual_account', 0);
    }

    public function test_status_expire_resmi_menghasilkan_va_baru_dan_riwayat(): void
    {
        $this->setupSandbox();
        $this->statusApi();
        $akun = $this->akun('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($tagihan): void {
            $mock->shouldReceive('buatVirtualAccount')->once()
                ->with((int) $tagihan->id_tagihan)
                ->andReturnUsing(function () use ($tagihan): array {
                    TagihanUKT::findOrFail($tagihan->id_tagihan)->update([
                        'virtual_account' => '53113890606570727926740',
                        'midtrans_order_id' => 'UKT-NEW-2',
                        'va_expires_at' => '2026-11-24 16:27:01',
                    ]);

                    return ['order_id' => 'UKT-NEW-2'];
                });
        });

        $this->actingAs($akun)->post(route('mahasiswa.tagihan.va.renew', [
            'idTagihan' => $tagihan->id_tagihan,
        ]))->assertRedirect(route('mahasiswa.tagihan.index', [
            'semester' => 'Genap 2027/2028',
        ]))->assertSessionHas('va_success');

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'virtual_account' => '53113890606570727926740',
            'midtrans_order_id' => 'UKT-NEW-2',
            'status_bayar' => 'Belum Dibayar',
        ]);

        $this->assertDatabaseHas('riwayat_virtual_account', [
            'id_tagihan' => $tagihan->id_tagihan,
            'virtual_account_lama' => '53113794609664399508145',
            'midtrans_order_id_lama' => 'UKT-OLD-1',
            'status_midtrans_lama' => 'expire',
            'virtual_account_baru' => '53113890606570727926740',
            'midtrans_order_id_baru' => 'UKT-NEW-2',
        ]);

        Http::assertSentCount(1);
    }

    public function test_status_pending_tidak_boleh_diganti(): void
    {
        $this->assertStatusDitolak('pending');
    }

    public function test_status_settlement_tidak_boleh_diganti(): void
    {
        $this->assertStatusDitolak('settlement');
    }

    private function assertStatusDitolak(string $status): void
    {
        $this->setupSandbox();
        $this->statusApi($status);
        $akun = $this->akun('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('buatVirtualAccount');
        });

        $this->actingAs($akun)->post(route('mahasiswa.tagihan.va.renew', [
            'idTagihan' => $tagihan->id_tagihan,
        ]))->assertSessionHas('va_error');

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'virtual_account' => '53113794609664399508145',
        ]);
        $this->assertDatabaseCount('riwayat_virtual_account', 0);
    }

    public function test_va_tanpa_order_id_tidak_boleh_diganti(): void
    {
        $this->setupSandbox();
        $akun = $this->akun('255150400111001');
        $tagihan = $this->tagihan('255150400111001');
        $tagihan->update(['midtrans_order_id' => null]);
        Http::fake();

        $this->actingAs($akun)->post(route('mahasiswa.tagihan.va.renew', [
            'idTagihan' => $tagihan->id_tagihan,
        ]))->assertSessionHas('va_error');

        Http::assertNothingSent();
        $this->assertDatabaseCount('riwayat_virtual_account', 0);
    }

    public function test_status_api_gagal_tidak_menghapus_va_lama(): void
    {
        $this->setupSandbox();
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response([], 503)]);
        $akun = $this->akun('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->actingAs($akun)->post(route('mahasiswa.tagihan.va.renew', [
            'idTagihan' => $tagihan->id_tagihan,
        ]))->assertSessionHas('va_error');

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'virtual_account' => '53113794609664399508145',
        ]);
    }

    public function test_va_tidak_diperbarui_jika_nomor_va_midtrans_berbeda(): void
    {
        $this->setupSandbox();
        $this->statusApi('expire', '5311000000000000000');
        $akun = $this->akun('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->actingAs($akun)->post(route('mahasiswa.tagihan.va.renew', [
            'idTagihan' => $tagihan->id_tagihan,
        ]))->assertSessionHas('va_error');

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'virtual_account' => '53113794609664399508145',
        ]);
    }

    public function test_gagal_menerbitkan_va_baru_memulihkan_va_lama(): void
    {
        $this->setupSandbox();
        $this->statusApi();
        $akun = $this->akun('255150400111001');
        $tagihan = $this->tagihan('255150400111001');

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('buatVirtualAccount')
                ->once()
                ->andThrow(new RuntimeException('Midtrans charge gagal.'));
        });

        $this->actingAs($akun)->post(route('mahasiswa.tagihan.va.renew', [
            'idTagihan' => $tagihan->id_tagihan,
        ]))->assertSessionHas('va_error');

        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'virtual_account' => '53113794609664399508145',
            'midtrans_order_id' => 'UKT-OLD-1',
        ]);
        $this->assertDatabaseCount('riwayat_virtual_account', 0);
    }
}
