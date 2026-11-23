<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel PengajuanUKT sesuai PDM/DDL Modul 2 (UC-04 dan UC-05).
        Schema::create('pengajuan_ukt', function (Blueprint $table) {
            $table->increments('id_pengajuan');

            $table->string('nim', 15);

            $table->string('semester', 20);

            $table->text('alasan');

            // Path file di storage server (bukan isi file).
            $table->string('dokumen_pendukung', 255);

            $table->string('status_verifikasi', 20)
                ->default('Menunggu Verifikasi');

            $table->text('catatan_verifikator')->nullable();

            $table->foreign('nim')
                ->references('nim')
                ->on('mahasiswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_ukt');
    }
};
