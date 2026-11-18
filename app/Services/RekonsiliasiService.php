<?php

namespace App\Services;

use App\Models\TagihanUKT;
use App\Models\TransaksiRekonsiliasi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RekonsiliasiService
{
    /**
     * Memproses rekonsiliasi pembayaran Virtual Account.
     */
    public function proses(array $data): array
    {
        return DB::transaction(function () use ($data) {

            // 1. Periksa referensi transaksi duplikat.
            $transaksiDuplikat = TransaksiRekonsiliasi::where(
                'referensi_bank',
                $data['referensi_bank']
            )->exists();

            if ($transaksiDuplikat) {
                return [
                    'accepted' => false,
                    'status' => 'Duplikat',
                    'message' => 'Transaksi sudah pernah diproses.',
                ];
            }

            // 2. Cari tagihan berdasarkan nomor Virtual Account.
            $tagihan = TagihanUKT::where(
                'virtual_account',
                $data['nomor_va']
            )
                ->lockForUpdate()
                ->first();

            $status = 'Tidak Sesuai';
            $pesan = 'Nomor Virtual Account tidak ditemukan.';
            $idTagihan = $tagihan?->id_tagihan;

            // 3. Jika tagihan ditemukan, periksa nominal pembayaran.
            if ($tagihan !== null) {

                $nominalCocok = $this->toMinorUnits(
                    $tagihan->nominal
                ) === $this->toMinorUnits(
                    $data['nominal_bayar']
                );

                // 4. Periksa kecocokan nominal dan status tagihan.
                if (!$nominalCocok) {
                    $pesan = 'Nominal pembayaran tidak sesuai dengan tagihan.';
                } elseif ($tagihan->status_bayar !== 'Belum Dibayar') {
                    $pesan = 'Tagihan tidak berada dalam status Belum Dibayar.';
                } else {
                    // 5. Pembayaran sesuai: perbarui status tagihan.
                    $tagihan->update([
                        'status_bayar' => 'Lunas',
                    ]);

                    $status = 'Sesuai';
                    $pesan = 'Pembayaran berhasil direkonsiliasi.';
                }
            }

            // 6. Simpan seluruh transaksi yang berhasil diproses.
            $transaksi = TransaksiRekonsiliasi::create([
                'id_tagihan' => $idTagihan,
                'virtual_account' => $data['nomor_va'],
                'referensi_bank' => $data['referensi_bank'],
                'nominal_bayar' => $data['nominal_bayar'],
                'waktu_transaksi' => $data['waktu_transaksi'],
                'status_flag' => $status,
            ]);

            return [
                'accepted' => true,
                'id_transaksi' => $transaksi->id_transaksi,
                'id_tagihan' => $idTagihan,
                'status' => $status,
                'message' => $pesan,
            ];
        });
    }

    /**
     * Membandingkan nominal dalam satuan sen
     * untuk menghindari perbandingan floating point.
     */
    private function toMinorUnits(string|int|float $amount): int
    {
        $value = trim((string) $amount);

        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException(
                'Format nominal pembayaran tidak valid.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }
}
