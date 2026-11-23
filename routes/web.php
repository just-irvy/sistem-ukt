<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenetapanTagihanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
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
if (app()->environment('local')) {
    Route::get('/mahasiswa/tagihan', [\App\Http\Controllers\TagihanMahasiswaController::class, 'index'])
        ->name('mahasiswa.tagihan.index');
}

// UC-07: permintaan Virtual Account untuk pratinjau UI-03 di komputer lokal.
if (app()->environment('local')) {
    \Illuminate\Support\Facades\Route::post(
        '/mahasiswa/tagihan/{idTagihan}/virtual-account',
        [\App\Http\Controllers\TagihanMahasiswaController::class, 'buatVirtualAccount']
    )->whereNumber('idTagihan')->name('mahasiswa.tagihan.va');
}


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
});
