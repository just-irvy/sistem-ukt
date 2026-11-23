<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Akun demo untuk menguji Login (UC-02). Semua password: password123
 * Jalankan: php artisan db:seed --class=AkunTestSeeder
 */
class AkunTestSeeder extends Seeder
{
    public function run(): void
    {
        // Akun mahasiswa untuk Budi Santoso (data dari UktTestDataSeeder).
        $akunMahasiswa = Akun::updateOrCreate(
            ['username' => '255150400111099'],
            ['password' => 'password123', 'role' => Akun::ROLE_MAHASISWA]
        );

        DB::table('mahasiswa')->updateOrInsert(
            ['nim' => '255150400111099'],
            [
                'nama' => 'Budi Santoso',
                'no_telp' => '081234567890',
                'fakultas' => 'Ilmu Komputer',
                'prodi' => 'Sistem Informasi',
                'id_akun' => $akunMahasiswa->id_akun,
            ]
        );

        // Akun pegawai (tabel Verifikator / BagianKeuangan dikerjakan anggota lain).
        Akun::updateOrCreate(
            ['username' => '198001012005011001'],
            ['password' => 'password123', 'role' => Akun::ROLE_KEUANGAN]
        );

        $akunVerifikator = Akun::updateOrCreate(
            ['username' => '198502022010012002'],
            ['password' => 'password123', 'role' => Akun::ROLE_VERIFIKATOR]
        );

        // Data Verifikator (UC-05); NIP sama dengan username akun.
        DB::table('verifikator')->updateOrInsert(
            ['nip' => '198502022010012002'],
            [
                'nama' => 'Verifikator UKT',
                'id_akun' => $akunVerifikator->id_akun,
            ]
        );

    }
}
