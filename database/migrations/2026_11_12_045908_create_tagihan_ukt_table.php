<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_ukt', function (Blueprint $table) {
            $table->increments('id_tagihan');

            $table->string('nim', 15);

            $table->string('semester', 20);

            $table->decimal('nominal', 12, 2);

            $table->date('batas_waktu');

            $table->string('status_bayar', 15)
                ->default('Belum Dibayar');

            $table->string('virtual_account', 20)
                ->nullable()
                ->unique();

            $table->foreign('nim')
                ->references('nim')
                ->on('mahasiswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['nim', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_ukt');
    }
};