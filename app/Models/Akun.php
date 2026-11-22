<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Akun extends Authenticatable
{
    public const ROLE_MAHASISWA = 'Mahasiswa';

    public const ROLE_VERIFIKATOR = 'Verifikator';

    public const ROLE_KEUANGAN = 'Bagian Keuangan';

    protected $table = 'akun';

    protected $primaryKey = 'id_akun';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(
            Mahasiswa::class,
            'id_akun',
            'id_akun'
        );
    }

    public function verifikator(): HasOne
    {
        return $this->hasOne(
            Verifikator::class,
            'id_akun',
            'id_akun'
        );
    }

    /**
     * PS-01: membuat akun (role Mahasiswa) beserta data mahasiswa.
     * Username akun mahasiswa adalah NIM. Keduanya disimpan dalam
     * satu transaksi agar tidak ada akun tanpa data mahasiswa.
     */
    public static function createAkun(array $dataAkun): self
    {
        return DB::transaction(function () use ($dataAkun) {
            $akun = static::create([
                'username' => $dataAkun['nim'],
                'password' => $dataAkun['password'], // di-hash oleh cast
                'role' => self::ROLE_MAHASISWA,
            ]);

            Mahasiswa::create([
                'nim' => $dataAkun['nim'],
                'nama' => $dataAkun['nama'],
                'nomor_telepon' => $dataAkun['no_telp'],
                'fakultas' => $dataAkun['fakultas'],
                'prodi' => $dataAkun['prodi'],
                'id_akun' => $akun->id_akun,
            ]);

            return $akun;
        });
    }

    /**
     * PS-02: memeriksa kecocokan username dan password.
     */
    public static function verifyCredentials(
        string $username,
        string $password
    ): bool {
        $akun = static::where('username', $username)->first();

        return $akun !== null
            && Hash::check($password, $akun->password);
    }

    /**
     * PS-02: mengambil role berdasarkan username.
     */
    public static function getRole(string $username): ?string
    {
        return static::where('username', $username)->value('role');
    }
}
