<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_rekonsiliasi', function (Blueprint $table) {
            $table->string('referensi_bank', 64)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_rekonsiliasi', function (Blueprint $table) {
            $table->string('referensi_bank', 30)->change();
        });
    }
};
