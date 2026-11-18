<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekonsiliasiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MidtransNotificationController extends Controller
{
    public function handle(
        Request $request,
        RekonsiliasiService $service
    ): JsonResponse {
        // 1. Validasi struktur notifikasi.
        $data = $request->validate([
            'order_id' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'string'],
            'signature_key' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'transaction_id' => ['required', 'string', 'max:64'],
            'transaction_time' => ['required', 'date'],
            'payment_type' => ['required', 'string'],
            'merchant_id' => ['required', 'string'],
            'fraud_status' => ['sometimes', 'nullable', 'string'],
            'va_numbers' => ['sometimes', 'array'],
            'va_numbers.0.va_number' => ['sometimes', 'string'],
        ]);

        // 2. Pastikan Server Key tersedia.
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '') {
            return response()->json([
                'message' => 'Konfigurasi Server Key tidak tersedia.',
            ], 500);
        }

        // 3. Verifikasi signature standar Midtrans.
        $expectedSignature = hash(
            'sha512',
            $data['order_id']
                . $data['status_code']
                . $data['gross_amount']
                . $serverKey
        );

        $signatureValid = hash_equals(
            $expectedSignature,
            strtolower($data['signature_key'])
        );

        // Default: gunakan data notifikasi yang sudah tervalidasi.
        $verifiedData = array_merge($data, [
            'va_numbers' => $request->input('va_numbers', []),
            'fraud_status' => $request->input('fraud_status'),
        ]);

        // 4. Jika signature tidak cocok, verifikasi langsung
        //    menggunakan GET Status API Midtrans.
        if (! $signatureValid) {
            $baseUrl = config('midtrans.is_production')
                ? 'https://api.midtrans.com'
                : 'https://api.sandbox.midtrans.com';

            try {
                $statusResponse = Http::withBasicAuth(
                    $serverKey,
                    ''
                )
                    ->acceptJson()
                    ->timeout(8)
                    ->get(
                        $baseUrl
                            . '/v2/'
                            . rawurlencode($data['order_id'])
                            . '/status'
                    );
            } catch (ConnectionException $exception) {
                report($exception);

                return response()->json([
                    'message' => 'Tidak dapat menghubungi Status API Midtrans.',
                ], 503);
            }

            if (! $statusResponse->successful()) {
                return response()->json([
                    'message' => 'Verifikasi transaksi melalui Midtrans gagal.',
                ], 503);
            }

            $statusData = $statusResponse->json();

            if (! is_array($statusData)) {
                return response()->json([
                    'message' => 'Respons Status API Midtrans tidak valid.',
                ], 503);
            }

            // Cocokkan notifikasi dengan data yang dikonfirmasi
            // langsung oleh server Midtrans.
            $statusVa = $statusData['va_numbers'][0]['va_number'] ?? null;
            $notificationVa = $request->input('va_numbers.0.va_number');

            $matches = [
                'order_id' => ($statusData['order_id'] ?? null)
                    === $data['order_id'],

                'transaction_id' => ($statusData['transaction_id'] ?? null)
                    === $data['transaction_id'],

                'status_code' => ($statusData['status_code'] ?? null)
                    === $data['status_code'],

                'gross_amount' => ($statusData['gross_amount'] ?? null)
                    === $data['gross_amount'],

                'transaction_status' => ($statusData['transaction_status'] ?? null)
                    === $data['transaction_status'],

                'transaction_time' => ($statusData['transaction_time'] ?? null)
                    === $data['transaction_time'],

                'payment_type' => ($statusData['payment_type'] ?? null)
                    === $data['payment_type'],

                'merchant_id_notification' => $data['merchant_id']
                    === (string) config('midtrans.merchant_id'),

                'merchant_id_status_api' => ($statusData['merchant_id'] ?? null)
                    === (string) config('midtrans.merchant_id'),

                'va_number' => is_string($statusVa)
                    && ($notificationVa === null || $notificationVa === $statusVa),
            ];

            // Jika notifikasi tidak sesuai dengan data Midtrans,
            // jangan proses transaksi.
            if (in_array(false, $matches, true)) {
                logger()->warning(
                    'Notifikasi Midtrans tidak cocok dengan Status API.',
                    ['order_id' => $data['order_id']]
                );

                return response()->json([
                    'message' => 'Data notifikasi tidak cocok dengan Status API Midtrans.',
                ], 403);
            }

            // Gunakan data dari respons Midtrans, bukan payload
            // yang belum berhasil diverifikasi signature-nya.
            $verifiedData = $statusData;
        }

        // 5. Pastikan Merchant ID sesuai.
        if (
            ($verifiedData['merchant_id'] ?? null)
            !== (string) config('midtrans.merchant_id')
        ) {
            return response()->json([
                'message' => 'Merchant ID tidak sesuai.',
            ], 403);
        }

        // 6. Hanya proses transaksi yang berhasil.
        if (
            ($verifiedData['status_code'] ?? null) !== '200'
            || ! in_array(
                $verifiedData['transaction_status'] ?? null,
                ['settlement', 'capture'],
                true
            )
        ) {
            return response()->json([
                'message' => 'Transaksi belum berstatus pembayaran berhasil.',
                'processed' => false,
            ], 200);
        }

        // 7. Pastikan transaksi diterima oleh sistem fraud Midtrans.
        $fraudStatus = strtolower(
            (string) ($verifiedData['fraud_status'] ?? '')
        );

        if ($fraudStatus !== '' && $fraudStatus !== 'accept') {
            return response()->json([
                'message' => 'Transaksi belum mendapatkan status fraud accept.',
                'processed' => false,
            ], 200);
        }

        // 8. UC-08 ini memproses pembayaran melalui Bank Transfer.
        if (($verifiedData['payment_type'] ?? null) !== 'bank_transfer') {
            return response()->json([
                'message' => 'Metode pembayaran bukan Bank Transfer.',
                'processed' => false,
            ], 200);
        }

        // 9. Ambil VA dari data yang sudah diverifikasi.
        $nomorVA = $verifiedData['va_numbers'][0]['va_number'] ?? null;

        if (! is_string($nomorVA) || $nomorVA === '') {
            return response()->json([
                'message' => 'Nomor Virtual Account tidak ditemukan.',
            ], 422);
        }

        // 10. Teruskan ke proses rekonsiliasi UC-08.
        $result = $service->proses([
            'nomor_va' => $nomorVA,
            'referensi_bank' => $verifiedData['transaction_id'],
            'nominal_bayar' => $verifiedData['gross_amount'],
            'waktu_transaksi' => $verifiedData['transaction_time'],
        ]);

        // 11. Notifikasi duplikat tetap diakui dengan HTTP 200.
        if (! $result['accepted']) {
            return response()->json([
                'message' => 'Transaksi sudah pernah diproses.',
                'status' => $result['status'],
                'processed' => false,
            ], 200);
        }

        return response()->json([
            'message' => 'Notifikasi Midtrans berhasil diproses.',
            'data' => $result,
        ], 200);
    }
}
