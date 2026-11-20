<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenetapanTagihanController;

Route::get('/', function () {
    return view('welcome');
});

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

