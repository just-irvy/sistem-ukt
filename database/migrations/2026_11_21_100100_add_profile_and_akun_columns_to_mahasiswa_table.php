<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom dibuat nullable agar data lama (seeder/test rekonsiliasi)
        // yang hanya berisi nim dan nama tetap valid. Kelengkapan data
        // untuk pendaftar baru dipaksa di RegistrasiController.
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->string('nomor_telepon', 15)->nullable()->after('nama');

            $table->string('fakultas', 50)->nullable()->after('nomor_telepon');

            $table->string('prodi', 50)->nullable()->after('fakultas');

            $table->unsignedInteger('id_akun')
                ->nullable()
                ->unique()
                ->after('prodi');

            $table->foreign('id_akun')
                ->references('id_akun')
                ->on('akun')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropForeign(['id_akun']);
            $table->dropUnique(['id_akun']);
            $table->dropColumn([
                'nomor_telepon',
                'fakultas',
                'prodi',
                'id_akun',
            ]);
        });
    }
};
