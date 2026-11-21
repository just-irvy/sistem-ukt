<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\TagihanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
});
