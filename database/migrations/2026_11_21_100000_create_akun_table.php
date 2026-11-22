<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Akun sesuai PDM/DDL Modul 2 (UC-01 Registrasi, UC-02 Login).
        Schema::create('akun', function (Blueprint $table) {
            $table->increments('id_akun');

            $table->string('username', 20)->unique();

            $table->string('password', 255);

            $table->string('role', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akun');
    }
};
