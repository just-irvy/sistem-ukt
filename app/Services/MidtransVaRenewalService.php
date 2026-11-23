<?php

namespace App\Services;

use App\Models\TagihanUKT;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * UC-07: terbitkan VA pengganti hanya setelah Midtrans mengonfirmasi
 * transaksi lama benar-benar berstatus expire. Tidak menyentuh UC-08.
 */
class MidtransVaRenewalService
{
    public function perbarui(int $idTagihan, MidtransService $midtransService): array
    {
        if ((bool) config('midtrans.is_production', false)) {
            throw new RuntimeException('Pembaruan VA hanya tersedia di Midtrans Sandbox.');
        }

        $serverKey = (string) config('midtrans.server_key');
        $merchantId = (string) config('midtrans.merchant_id');

        if ($serverKey === '' || $merchantId === '') {
            throw new RuntimeException('Konfigurasi Midtrans Sandbox belum lengkap.');
        }

        // Kunci baris menjaga agar dua permintaan bersamaan tidak
        // sama-sama mengganti VA pada tagihan yang sama.
        return DB::transaction(function () use ($idTagihan, $midtransService, $serverKey, $merchantId): array {
            $tagihan = TagihanUKT::query()
                ->lockForUpdate()
                ->findOrFail($idTagihan);

            if ($tagihan->status_bayar !== 'Belum Dibayar') {
                throw new RuntimeException('Tagihan sudah dibayar atau tidak dapat dibayarkan.');
            }

            $vaLama = (string) ($tagihan->virtual_account ?? '');
            $orderLama = (string) ($tagihan->midtrans_order_id ?? '');
            $waktuLama = $tagihan->va_expires_at;

            if ($vaLama === '' || $orderLama === '' || $waktuLama === null) {
                throw new RuntimeException('VA lama tidak memiliki data Midtrans lengkap. Perlu pemeriksaan Bagian Keuangan.');
            }

            $expiry = CarbonImmutable::parse($waktuLama, 'Asia/Jakarta');
            if ($expiry->greaterThan(CarbonImmutable::now('Asia/Jakarta'))) {
                throw new RuntimeException('VA lama belum melewati masa berlakunya.');
            }

            // Get Status API menggunakan Server Key pada backend;
            // tanggal di database saja bukan bukti bahwa VA telah expire.
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->timeout(8)
                ->get('https://api.sandbox.midtrans.com/v2/'.rawurlencode($orderLama).'/status');

            if (!$response->successful()) {
                throw new RuntimeException('Status transaksi lama tidak dapat diverifikasi di Midtrans.');
            }

            $status = $response->json();

            if (!is_array($status)
                || ($status['order_id'] ?? null) !== $orderLama
                || ($status['transaction_status'] ?? null) !== 'expire'
                || ($status['payment_type'] ?? null) !== 'bank_transfer'
                || !isset($status['gross_amount'])
                || !is_numeric($status['gross_amount'])
                || number_format((float) $status['gross_amount'], 2, '.', '') !== number_format((float) $tagihan->nominal, 2, '.', '')
                || (isset($status['merchant_id']) && $status['merchant_id'] !== $merchantId)
                || ($status['va_numbers'][0]['va_number'] ?? null) !== $vaLama
                || (isset($status['va_numbers'][0]['bank']) && strtolower((string) $status['va_numbers'][0]['bank']) !== 'bca')
            ) {
                // Pending, Settlement, status tidak dikenal, data VA/nominal berbeda:
                // jangan terbitkan VA kedua.
                throw new RuntimeException('Midtrans belum mengonfirmasi bahwa VA lama boleh diganti.');
            }

            // Clearing sementara terjadi DI DALAM transaksi DB. Jika
            // pembuatan VA gagal, rollback mengembalikan VA lama.
            $tagihan->update([
                'virtual_account' => null,
                'midtrans_order_id' => null,
                'va_expires_at' => null,
            ]);

            $hasil = $midtransService->buatVirtualAccount((int) $tagihan->id_tagihan);

            $tagihan->refresh();

            if (empty($tagihan->virtual_account)
                || empty($tagihan->midtrans_order_id)
                || $tagihan->midtrans_order_id === $orderLama
                || $tagihan->virtual_account === $vaLama
            ) {
                throw new RuntimeException('VA pengganti tidak berhasil tersimpan lengkap.');
            }

            DB::table('riwayat_virtual_account')->insert([
                'id_tagihan' => $tagihan->id_tagihan,
                'virtual_account_lama' => $vaLama,
                'midtrans_order_id_lama' => $orderLama,
                'va_expires_at_lama' => $waktuLama,
                'status_midtrans_lama' => 'expire',
                'virtual_account_baru' => $tagihan->virtual_account,
                'midtrans_order_id_baru' => $tagihan->midtrans_order_id,
                'created_at' => now(),
            ]);

            return $hasil;
        });
    }
}
