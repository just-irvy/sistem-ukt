<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RekonsiliasiController;
use App\Http\Controllers\Api\MidtransNotificationController;

Route::post(
    '/reconciliation/test',
    [RekonsiliasiController::class, 'store']
);

Route::post(
    '/midtrans/notification',
    [MidtransNotificationController::class, 'handle']
);