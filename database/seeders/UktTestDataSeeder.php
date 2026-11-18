<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UktTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Data mahasiswa untuk pengujian
        DB::table('mahasiswa')->updateOrInsert(
            ['nim' => '255150400111099'],
            [
                'nama' => 'Budi Santoso',
            ]
        );

        // Data tagihan UKT
        DB::table('tagihan_ukt')->updateOrInsert(
            [
                'nim' => '255150400111099',
                'semester' => 'Ganjil 2026/2027',
            ],
            [
                'nominal' => 5000000.00,
                'batas_waktu' => '2026-11-30',
                'status_bayar' => 'Belum Dibayar',
                'virtual_account' => '8808255150400111099',
            ]
        );
    }
}