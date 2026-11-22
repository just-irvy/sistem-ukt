<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            if (!Schema::hasColumn('mahasiswa', 'no_telp')) {
                $table->string('no_telp', 15)->nullable();
            }
            if (!Schema::hasColumn('mahasiswa', 'fakultas')) {
                $table->string('fakultas', 50)->nullable();
            }
            if (!Schema::hasColumn('mahasiswa', 'prodi')) {
                $table->string('prodi', 50)->nullable();
            }
            if (!Schema::hasColumn('mahasiswa', 'id_akun')) {
                $table->unsignedBigInteger('id_akun')->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropColumn(['no_telp', 'fakultas', 'prodi', 'id_akun']);
        });
    }
};