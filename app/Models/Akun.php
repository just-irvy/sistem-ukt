<?php

namespace App\Models;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class Akun extends Authenticatable
{
    public const ROLE_MAHASISWA = 'Mahasiswa';
    public const ROLE_KEUANGAN = 'Bagian Keuangan';
    public const ROLE_VERIFIKATOR = 'Verifikator';

    protected $table = 'akun';
    protected $primaryKey = 'id_akun';
    protected $fillable = ['username', 'password', 'role'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class, 'id_akun', 'id_akun');
    }
        public static function verifyCredentials(string $username, string $password): bool
    {
        $akun = static::where('username', $username)->first();

        return $akun !== null && Hash::check($password, $akun->password);
    }

    public static function getRole(string $username): ?string
    {
        return static::where('username', $username)->value('role');
    }

    // PS-01: membuat akun mahasiswa beserta datanya.
    public static function createAkun(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $akun = static::create([
                'username' => $data['nim'],
                'password' => $data['password'],
                'role' => self::ROLE_MAHASISWA,
            ]);

            Mahasiswa::create([
                'nim' => $data['nim'],
                'nama' => $data['nama'],
                'no_telp' => $data['no_telp'],
                'fakultas' => $data['fakultas'],
                'prodi' => $data['prodi'],
                'id_akun' => $akun->id_akun,
            ]);

            return $akun;
        });
    }

}