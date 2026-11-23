<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindak_lanjut_rekonsiliasi', function (Blueprint $table) {
            $table->id('id_tindak_lanjut');
            // Schema asal: transaksi_rekonsiliasi.id_transaksi = unsigned INT.
            $table->unsignedInteger('id_transaksi');
            // Schema hasil integrasi HT: akun.id_akun = unsigned BIGINT.
            $table->unsignedBigInteger('id_akun');
            $table->string('status_tindak_lanjut', 25);
            $table->text('catatan');
            $table->timestamps();

            $table->foreign('id_transaksi')
                ->references('id_transaksi')->on('transaksi_rekonsiliasi')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_akun')
                ->references('id_akun')->on('akun')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['id_transaksi', 'created_at'], 'tl_rekon_transaksi_waktu_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut_rekonsiliasi');
    }
};
