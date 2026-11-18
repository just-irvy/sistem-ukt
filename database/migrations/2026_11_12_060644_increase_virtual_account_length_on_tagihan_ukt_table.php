<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_ukt', function (Blueprint $table) {
            $table->string('virtual_account', 30)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        $hasLongVirtualAccount = DB::table('tagihan_ukt')
            ->whereNotNull('virtual_account')
            ->whereRaw('CHAR_LENGTH(virtual_account) > 20')
            ->exists();

        if ($hasLongVirtualAccount) {
            throw new RuntimeException(
                'Tidak dapat mengembalikan VARCHAR(20): '
                    . 'terdapat nomor Virtual Account lebih dari 20 karakter.'
            );
        }

        Schema::table('tagihan_ukt', function (Blueprint $table) {
            $table->string('virtual_account', 20)
                ->nullable()
                ->change();
        });
    }
};
