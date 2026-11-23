<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenetapanTagihanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\VerifikasiController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\TagihanController;

Route::redirect('/', '/login');

Route::prefix('keuangan')
    ->name('keuangan.')
    ->group(function () {
        Route::get(
            '/penetapan-tagihan',
            [PenetapanTagihanController::class, 'index']
        )->name('penetapan-tagihan.index');

        Route::post(
            '/penetapan-tagihan',
            [PenetapanTagihanController::class, 'store']
        )->name('penetapan-tagihan.store');
    });


// UC-01 Registrasi Akun
Route::get('/registrasi', [RegistrasiController::class, 'tampilkanFormRegistrasi'])
    ->name('registrasi');
Route::post('/registrasi', [RegistrasiController::class, 'submitRegistrasi'])
    ->name('registrasi.submit');

// UC-02 Login
Route::get('/login', [LoginController::class, 'tampilkanFormLogin'])
    ->name('login');
Route::post('/login', [LoginController::class, 'submitLogin'])
    ->middleware('throttle:10,1')
    ->name('login.submit');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    Route::get('/dashboard/mahasiswa', [DashboardController::class, 'mahasiswa'])
        ->name('dashboard.mahasiswa');
    Route::get('/dashboard/keuangan', [DashboardController::class, 'keuangan'])
        ->name('dashboard.keuangan');
    Route::get('/dashboard/verifikator', [DashboardController::class, 'verifikator'])
        ->name('dashboard.verifikator');

    // UC-03 Lihat Tagihan Semester
    // UI-03: halaman tagihan dengan dropdown periode semester.
    Route::get('/tagihan', [TagihanController::class, 'tampilkanHalamanTagihan'])
        ->name('tagihan.index');
    // PS-07: data tagihan satu semester (JSON).
    Route::get('/tagihan/semester', [TagihanController::class, 'getTagihanSemester'])
        ->name('tagihan.semester');

    // UC-07: pembayaran VA memakai sesi login dan otorisasi kepemilikan tagihan.
    Route::get('/mahasiswa/tagihan', [\App\Http\Controllers\TagihanMahasiswaController::class, 'index'])
        ->name('mahasiswa.tagihan.index');
    Route::post(
        '/mahasiswa/tagihan/{idTagihan}/virtual-account',
        [\App\Http\Controllers\TagihanMahasiswaController::class, 'buatVirtualAccount']
    )->whereNumber('idTagihan')->middleware('throttle:5,1')->name('mahasiswa.tagihan.va');

    // UC-04 Ajukan Penyesuaian UKT (Mahasiswa) | PS-03 | UI-04
    Route::get('/pengajuan', [PengajuanController::class, 'create'])
        ->name('pengajuan.create');
    Route::post('/pengajuan', [PengajuanController::class, 'store'])
        ->name('pengajuan.store');

    // UC-05 Verifikasi Pengajuan UKT (Verifikator) | PS-04 | UI-05
    Route::get('/verifikasi', [VerifikasiController::class, 'index'])
        ->name('verifikasi.index');
    Route::get('/verifikasi/{idPengajuan}', [VerifikasiController::class, 'show'])
        ->whereNumber('idPengajuan')->name('verifikasi.show');
    Route::get('/verifikasi/{idPengajuan}/dokumen', [VerifikasiController::class, 'dokumen'])
        ->whereNumber('idPengajuan')->name('verifikasi.dokumen');
    Route::post('/verifikasi/{idPengajuan}', [VerifikasiController::class, 'proses'])
        ->whereNumber('idPengajuan')->name('verifikasi.proses');
});

// UC-09: Riwayat & Hasil Rekonsiliasi (DBD)
Route::middleware('auth')
    ->prefix('keuangan')
    ->name('keuangan.')
    ->group(function () {
        Route::get('/riwayat-rekonsiliasi', [\App\Http\Controllers\RiwayatRekonsiliasiController::class, 'index'])
            ->name('riwayat-rekonsiliasi.index');
        Route::get('/riwayat-rekonsiliasi/{idTransaksi}', [\App\Http\Controllers\RiwayatRekonsiliasiController::class, 'detail'])
            ->whereNumber('idTransaksi')
            ->name('riwayat-rekonsiliasi.detail');
        Route::post('/riwayat-rekonsiliasi/{idTransaksi}/tindak-lanjut', [\App\Http\Controllers\RiwayatRekonsiliasiController::class, 'simpanTindakLanjut'])
            ->whereNumber('idTransaksi')
            ->name('riwayat-rekonsiliasi.tindak-lanjut');
    });
