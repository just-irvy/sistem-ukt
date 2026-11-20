<?php

namespace App\Services;

use App\Models\TagihanUKT;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Midtrans\Config;
use Midtrans\CoreApi;

class MidtransService
{
    /**
     * Menginisialisasi konfigurasi SDK Midtrans.
     */
    private function configure(): void
    {
        $serverKey = config('midtrans.server_key');

        if (empty($serverKey)) {
            throw new RuntimeException(
                'Midtrans Server Key belum dikonfigurasi.'
            );
        }

        Config::$serverKey = $serverKey;

        Config::$isProduction = (bool) config(
            'midtrans.is_production',
            false
        );

        Config::$isSanitized = true;
    }

    /**
     * Membuat Virtual Account BCA untuk tagihan UKT.
     */
    public function buatVirtualAccount(
        int $idTagihan,
        string $bank = 'bca'
    ): array {
        // 1. Validasi bank yang digunakan.
        if ($bank !== 'bca') {
            throw new InvalidArgumentException(
                'Untuk tahap ini hanya BCA Virtual Account yang tersedia.'
            );
        }

        // 2. Cari tagihan berdasarkan ID.
        $tagihan = TagihanUKT::with('mahasiswa')
            ->findOrFail($idTagihan);

        // 3. Pastikan tagihan belum dibayar.
        if ($tagihan->status_bayar !== 'Belum Dibayar') {
            throw new RuntimeException(
                'Tagihan tidak dapat dibayarkan.'
            );
        }

        // 4. Pastikan nomor VA belum pernah diterbitkan.
        if (!empty($tagihan->virtual_account)) {
            throw new RuntimeException(
                'Tagihan sudah memiliki Virtual Account.'
            );
        }

        // 5. Validasi nominal UKT.
        $nominal = (float) $tagihan->nominal;

        if ($nominal <= 0 || floor($nominal) !== $nominal) {
            throw new InvalidArgumentException(
                'Nominal UKT harus berupa bilangan bulat yang lebih besar dari nol.'
            );
        }

        // 6. Konfigurasi SDK.
        $this->configure();

        // 7. Buat order ID unik untuk Midtrans.
        $orderId = 'UKT-' . $idTagihan . '-'
            . Str::upper(Str::random(12));

        // 8. Siapkan parameter transaksi.
        $params = [
            'payment_type' => 'bank_transfer',

            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $nominal,
            ],

            'bank_transfer' => [
                'bank' => 'bca',
            ],
        ];

        // 9. Kirim permintaan pembuatan VA ke Midtrans.
        $response = CoreApi::charge($params);

        // 10. Periksa apakah Midtrans memberikan nomor VA.
        $vaNumber = $response->va_numbers[0]->va_number ?? null;

        if (
            (string) ($response->status_code ?? '') !== '201'
            || empty($vaNumber)
        ) {
            throw new RuntimeException(
                $response->status_message
                    ?? 'Midtrans gagal menerbitkan Virtual Account.'
            );
        }

        // 11. Normalisasi waktu kedaluwarsa dari Midtrans ke WIB.
        // Kolom DATETIME MySQL tidak memiliki informasi zona waktu.
        // va_expires_at disimpan sebagai waktu lokal Asia/Jakarta (WIB),
        // bukan UTC. Kode pembacanya harus menggunakan konvensi yang sama.
        $expiryTimeWib = null;
        $expiryTimeResponse = $response->expiry_time ?? null;

        if (is_string($expiryTimeResponse) && trim($expiryTimeResponse) !== '') {
            try {
                $expiryTimeWib = CarbonImmutable::parse(
                    $expiryTimeResponse,
                    'Asia/Jakarta'
                )->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            } catch (Throwable $exception) {
                // Jika format tidak dikenali, jangan mengarang batas berlaku.
                // Nomor VA yang berhasil diterbitkan tetap harus disimpan.
                report($exception);
            }
        }

        // Simpan ketiga informasi yang diperoleh dari satu respons charge.
        // VA lama tidak disentuh karena diperiksa sebelum pemanggilan API.
        $orderIdMidtrans = (string) ($response->order_id ?? $orderId);

        $tagihan->update([
            'virtual_account' => $vaNumber,
            'midtrans_order_id' => $orderIdMidtrans,
            'va_expires_at' => $expiryTimeWib,
        ]);

        // 12. Kembalikan informasi transaksi.
        return [
            'id_tagihan' => $tagihan->id_tagihan,
            'order_id' => $orderIdMidtrans,
            'transaction_id' => $response->transaction_id,
            'bank' => 'bca',
            'virtual_account' => $vaNumber,
            'nominal' => $response->gross_amount,
            'transaction_status' => $response->transaction_status,
            'expiry_time' => $response->expiry_time ?? null,
        ];
    }
}
