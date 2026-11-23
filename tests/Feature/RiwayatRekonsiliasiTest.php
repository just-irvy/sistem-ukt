<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use App\Models\TransaksiRekonsiliasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC-09: daftar riwayat, filter, otorisasi, dan catatan tindak lanjut. */
class RiwayatRekonsiliasiTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $role): Akun
    {
        return Akun::create([
            'username' => 'uji_' . str_replace(' ', '_', $role),
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function tagihan(): TagihanUKT
    {
        Mahasiswa::create([
            'nim' => '255150400111099',
            'nama' => 'Mahasiswa Pengujian',
        ]);

        return TagihanUKT::create([
            'nim' => '255150400111099',
            'semester' => 'Ganjil 2026/2027',
            'nominal' => 5000000,
            'batas_waktu' => '2026-12-31',
            'status_bayar' => 'Belum Dibayar',
            'virtual_account' => '53113000000000000000001',
        ]);
    }

    private function transaksi(?TagihanUKT $tagihan, string $status = 'Tidak Sesuai', string $referensi = 'TRX-UJI-01'): TransaksiRekonsiliasi
    {
        return TransaksiRekonsiliasi::create([
            'id_tagihan' => $tagihan?->id_tagihan,
            'virtual_account' => $tagihan?->virtual_account ?? '53113000000000000000002',
            'referensi_bank' => $referensi,
            'nominal_bayar' => 4500000,
            'waktu_transaksi' => '2026-11-23 10:00:00',
            'status_flag' => $status,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('keuangan.riwayat-rekonsiliasi.index'))
            ->assertRedirect(route('login'));
    }

    public function test_mahasiswa_tidak_bisa_membuka_riwayat(): void
    {
        $this->actingAs($this->akun(Akun::ROLE_MAHASISWA))
            ->get(route('keuangan.riwayat-rekonsiliasi.index'))
            ->assertForbidden();
    }

    public function test_verifikator_tidak_bisa_membuka_detail(): void
    {
        $transaksi = $this->transaksi(null);
        $this->actingAs($this->akun(Akun::ROLE_VERIFIKATOR))
            ->get(route('keuangan.riwayat-rekonsiliasi.detail', $transaksi->id_transaksi))
            ->assertForbidden();
    }

    public function test_bagian_keuangan_melihat_riwayat_sesuai_dan_bermasalah(): void
    {
        $tagihan = $this->tagihan();
        $this->transaksi($tagihan, 'Tidak Sesuai', 'TRX-BERMASALAH');
        $this->transaksi($tagihan, 'Sesuai', 'TRX-SESUAI');

        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->get(route('keuangan.riwayat-rekonsiliasi.index'))
            ->assertOk()
            ->assertSee('TRX-BERMASALAH')
            ->assertSee('TRX-SESUAI')
            ->assertSee('Mahasiswa Pengujian');
    }

    public function test_riwayat_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->get(route('keuangan.riwayat-rekonsiliasi.index'))
            ->assertOk()
            ->assertSee('Belum ada riwayat transaksi untuk filter yang dipilih.');
    }

    public function test_filter_status_hanya_menampilkan_status_yang_dipilih(): void
    {
        $tagihan = $this->tagihan();
        $this->transaksi($tagihan, 'Sesuai', 'TRX-PILIH-SESUAI');
        $this->transaksi($tagihan, 'Tidak Sesuai', 'TRX-PILIH-BERMASALAH');

        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->get(route('keuangan.riwayat-rekonsiliasi.index', ['status' => 'bermasalah']))
            ->assertOk()
            ->assertSee('TRX-PILIH-BERMASALAH')
            ->assertDontSee('TRX-PILIH-SESUAI');
    }

    public function test_filter_periode_tidak_menampilkan_transaksi_tanpa_tagihan(): void
    {
        $tagihan = $this->tagihan();
        $this->transaksi($tagihan, 'Sesuai', 'TRX-SEMESTER');
        $this->transaksi(null, 'Tidak Sesuai', 'TRX-TANPA-TAGIHAN');

        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->get(route('keuangan.riwayat-rekonsiliasi.index', [
                'semester' => 'Ganjil 2026/2027',
            ]))
            ->assertOk()
            ->assertSee('TRX-SEMESTER')
            ->assertDontSee('TRX-TANPA-TAGIHAN');
    }

    public function test_filter_tanggal_dan_referensi_bank(): void
    {
        $this->transaksi(null, 'Tidak Sesuai', 'TRX-CARI-123');

        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->get(route('keuangan.riwayat-rekonsiliasi.index', [
                'tanggal_mulai' => '2026-11-23',
                'tanggal_akhir' => '2026-11-23',
                'cari' => 'TRX-CARI-123',
            ]))
            ->assertOk()
            ->assertSee('TRX-CARI-123');
    }

    public function test_catatan_tindak_lanjut_tersimpan_tetapi_tagihan_tidak_berubah(): void
    {
        $tagihan = $this->tagihan();
        $transaksi = $this->transaksi($tagihan);
        $akun = $this->akun(Akun::ROLE_KEUANGAN);

        $this->actingAs($akun)
            ->post(route('keuangan.riwayat-rekonsiliasi.tindak-lanjut', $transaksi->id_transaksi), [
                'status_tindak_lanjut' => 'Dalam Penanganan',
                'catatan' => 'Nominal tidak cocok, periksa bukti transaksi dan hubungi mahasiswa.',
            ])
            ->assertRedirect(route('keuangan.riwayat-rekonsiliasi.detail', $transaksi->id_transaksi));

        $this->assertDatabaseHas('tindak_lanjut_rekonsiliasi', [
            'id_transaksi' => $transaksi->id_transaksi,
            'id_akun' => $akun->id_akun,
            'status_tindak_lanjut' => 'Dalam Penanganan',
        ]);
        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Belum Dibayar',
        ]);
    }

    public function test_catat_tindak_lanjut_hanya_diperbolehkan_pada_transaksi_bermasalah(): void
    {
        $transaksi = $this->transaksi(null, 'Sesuai');

        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->post(route('keuangan.riwayat-rekonsiliasi.tindak-lanjut', $transaksi->id_transaksi), [
                'status_tindak_lanjut' => 'Selesai Ditinjau',
                'catatan' => 'Catatan ini tidak seharusnya disimpan.',
            ])
            ->assertForbidden();
        $this->assertDatabaseCount('tindak_lanjut_rekonsiliasi', 0);
    }

    public function test_catatan_tidak_valid_ditolak(): void
    {
        $transaksi = $this->transaksi(null);
        $this->actingAs($this->akun(Akun::ROLE_KEUANGAN))
            ->from(route('keuangan.riwayat-rekonsiliasi.detail', $transaksi->id_transaksi))
            ->post(route('keuangan.riwayat-rekonsiliasi.tindak-lanjut', $transaksi->id_transaksi), [
                'status_tindak_lanjut' => 'Lunas',
                'catatan' => 'Pendek',
            ])
            ->assertSessionHasErrors(['status_tindak_lanjut', 'catatan']);
        $this->assertDatabaseCount('tindak_lanjut_rekonsiliasi', 0);
    }

    public function test_akses_post_mahasiswa_ditolak(): void
    {
        $transaksi = $this->transaksi(null);
        $this->actingAs($this->akun(Akun::ROLE_MAHASISWA))
            ->post(route('keuangan.riwayat-rekonsiliasi.tindak-lanjut', $transaksi->id_transaksi), [
                'status_tindak_lanjut' => 'Dalam Penanganan',
                'catatan' => 'Mencoba menulis catatan sebagai mahasiswa.',
            ])
            ->assertForbidden();
    }
}
