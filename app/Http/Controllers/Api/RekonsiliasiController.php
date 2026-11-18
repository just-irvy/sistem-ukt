<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekonsiliasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RekonsiliasiController extends Controller
{
    public function store(
        Request $request,
        RekonsiliasiService $service
    ): JsonResponse {
        // Validasi data transaksi yang diterima API.
        $validated = $request->validate([
            'nomor_va' => ['required', 'string', 'max:20'],
            'referensi_bank' => ['required', 'string', 'max:30'],
            'nominal_bayar' => ['required', 'numeric', 'gt:0'],
            'waktu_transaksi' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        try {
            // Teruskan transaksi ke service rekonsiliasi.
            $result = $service->proses($validated);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => 'Data transaksi tidak valid.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        // Referensi bank sudah pernah diproses.
        if (!$result['accepted']) {
            return response()->json([
                'message' => $result['message'],
                'status' => $result['status'],
            ], 409);
        }

        // Transaksi berhasil diproses, baik sesuai maupun tidak sesuai.
        return response()->json([
            'message' => $result['message'],
            'data' => $result,
        ], 200);
    }
}