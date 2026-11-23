<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Verifikator sesuai PDM/DDL Modul 2 (UC-05 Verifikasi Pengajuan UKT).
        Schema::create('verifikator', function (Blueprint $table) {
            $table->string('nip', 18)->primary();

            $table->string('nama', 100);

            $table->unsignedBigInteger('id_akun')->unique();

            $table->foreign('id_akun')
                ->references('id_akun')
                ->on('akun')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikator');
    }
};
