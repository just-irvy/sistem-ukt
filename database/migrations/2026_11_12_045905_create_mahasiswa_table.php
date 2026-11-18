<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->string('nim', 15)->primary();
            $table->string('nama', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};