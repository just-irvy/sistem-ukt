<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Arsip perubahan VA untuk UC-07; tidak mengganti data rekonsiliasi UC-08.
        Schema::create('riwayat_virtual_account', function (Blueprint $table) {
            $table->id('id_riwayat_va');
            $table->unsignedInteger('id_tagihan');
            $table->string('virtual_account_lama', 50);
            $table->string('midtrans_order_id_lama', 100);
            $table->dateTime('va_expires_at_lama');
            $table->string('status_midtrans_lama', 20);
            $table->string('virtual_account_baru', 50);
            $table->string('midtrans_order_id_baru', 100);
            $table->timestamp('created_at');

            $table->foreign('id_tagihan')
                ->references('id_tagihan')
                ->on('tagihan_ukt')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['id_tagihan', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_virtual_account');
    }
};
