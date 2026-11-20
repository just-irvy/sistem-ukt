<?php

namespace Tests\Feature;

use App\Http\Controllers\TagihanMahasiswaController;
use App\Models\Mahasiswa;
use App\Models\TagihanUKT;
use App\Services\MidtransService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class TagihanMahasiswaVaFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Database khusus tes: SQLite di memori, BUKAN database MySQL proyek.
        // Tidak menggunakan RefreshDatabase atau migration pada database asli.
        config()->set('database.connections.ukt_uc07_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('database.default', 'ukt_uc07_test');
        DB::purge('ukt_uc07_test');

        Schema::connection('ukt_uc07_test')->create('mahasiswa', function (Blueprint $table): void {
            $table->string('nim', 15)->primary();
            $table->string('nama', 100);
        });

        Schema::connection('ukt_uc07_test')->create('tagihan_ukt', function (Blueprint $table): void {
            $table->increments('id_tagihan');
            $table->string('nim', 15);
            $table->string('semester', 20);
            $table->decimal('nominal', 12, 2);
            $table->date('batas_waktu');
            $table->string('status_bayar', 20);
            $table->string('virtual_account', 30)->nullable();
            $table->string('midtrans_order_id', 100)->nullable();
            $table->dateTime('va_expires_at')->nullable();
        });

        // Controller saat ini membatasi pengujian pada environment local.
        // Penyesuaian ini berlaku hanya pada proses PHPUnit, bukan .env.
        $this->app->instance('env', 'local');
        config()->set('session.driver', 'array');

        // Environment local mengaktifkan perlindungan request forgery saat PHPUnit berjalan.
        // Nonaktifkan middleware PreventRequestForgery Laravel 13 hanya dalam test ini; bukan pada route aplikasi.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        // Route tes didaftarkan hanya selama test, tanpa mengubah routes/web.php.
        Route::middleware('web')->group(function (): void {
            Route::get('/__uji-uc07/tagihan', static fn () => 'Halaman uji UC-07')
                ->name('mahasiswa.tagihan.index');

            Route::post(
                '/__uji-uc07/tagihan/{idTagihan}/virtual-account',
                [TagihanMahasiswaController::class, 'buatVirtualAccount']
            )->whereNumber('idTagihan')->name('mahasiswa.tagihan.va');
        });

        // Route ditambahkan setelah aplikasi selesai booting pada PHPUnit.
        // Segarkan indeks nama route agar route(...) dapat menemukannya.
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_gagal_meminta_va_menampilkan_pesan_dan_tidak_mengubah_tagihan(): void
    {
        Mahasiswa::create([
            'nim' => '255150400111099',
            'nama' => 'Mahasiswa Uji',
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

        // MidtransService digantikan mock; tidak ada request ke Midtrans.
        $this->mock(MidtransService::class, function (MockInterface $mock) use ($tagihan): void {
            $mock->shouldReceive('buatVirtualAccount')
                ->once()
                ->with((int) $tagihan->id_tagihan)
                ->andThrow(new RuntimeException('Simulasi: API Midtrans tidak tersedia.'));
        });

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('mahasiswa.tagihan.va', [
                'idTagihan' => $tagihan->id_tagihan,
            ]), [
                'nim' => '255150400111099',
                'semester' => 'Genap 2030/2031',
            ]);

        $response->assertRedirect(route('mahasiswa.tagihan.index', [
            'nim' => '255150400111099',
            'semester' => 'Genap 2030/2031',
        ]));

        $response->assertSessionHas(
            'va_error',
            'Gagal memuat Virtual Account. Periksa konfigurasi Midtrans Sandbox dan log aplikasi, lalu coba lagi.'
        );

        // Periksa seluruh data pembayaran tidak berubah karena API gagal.
        $this->assertDatabaseHas('tagihan_ukt', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_bayar' => 'Belum Dibayar',
            'virtual_account' => null,
            'midtrans_order_id' => null,
            'va_expires_at' => null,
        ]);
    }
}
