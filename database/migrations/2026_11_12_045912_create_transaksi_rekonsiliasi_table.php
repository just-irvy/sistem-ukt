<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_rekonsiliasi', function (Blueprint $table) {
            $table->increments('id_transaksi');

            $table->unsignedInteger('id_tagihan')->nullable();

            $table->string('virtual_account', 20);

            $table->string('referensi_bank', 30)->unique();

            $table->decimal('nominal_bayar', 12, 2);

            $table->dateTime('waktu_transaksi');

            $table->string('status_flag', 20);

            $table->foreign('id_tagihan')
                ->references('id_tagihan')
                ->on('tagihan_ukt')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_rekonsiliasi');
    }
};