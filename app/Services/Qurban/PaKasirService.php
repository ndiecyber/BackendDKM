<?php

namespace App\Services\Qurban;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaKasirService
{
    private string $baseUrl;

    private string $projectSlug;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('pakasir.base_url');
        $this->projectSlug = config('pakasir.project_slug');
        $this->apiKey = config('pakasir.api_key');
    }

    /**
     * Create a payment transaction via PaKasir.
     *
     * @return array{payment_number: string, total_payment: int, expired_at: string, fee: int}
     *
     * @throws \Exception
     */
    public function createTransaction(string $method, string $orderId, int $amount): array
    {
        $response = Http::post("{$this->baseUrl}/api/transactioncreate/{$method}", [
            'project' => $this->projectSlug,
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('PaKasir createTransaction failed', [
                'method' => $method,
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

        $payment = $response->json('payment');

        return [
            'payment_number' => $payment['payment_number'],
            'total_payment' => $payment['total_payment'],
            'fee' => $payment['fee'],
            'expired_at' => $payment['expired_at'],
        ];
    }

    /**
     * Get transaction detail from PaKasir (for webhook double-check).
     */
    public function getTransactionDetail(string $orderId, int $amount): ?array
    {
        $response = Http::get("{$this->baseUrl}/api/transactiondetail", [
            'project' => $this->projectSlug,
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::warning('PaKasir getTransactionDetail failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json('transaction');
    }

    /**
     * Cancel a pending transaction via PaKasir.
     */
    public function cancelTransaction(string $orderId, int $amount): bool
    {
        $response = Http::post("{$this->baseUrl}/api/transactioncancel", [
            'project' => $this->projectSlug,
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::warning('PaKasir cancelTransaction failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Simulate a payment (sandbox only).
     */
    public function simulatePayment(string $orderId, int $amount): bool
    {
        if (! config('pakasir.sandbox')) {
            throw new \Exception('Payment simulation hanya tersedia di mode sandbox.');
        }

        $response = Http::post("{$this->baseUrl}/api/paymentsimulation", [
            'project' => $this->projectSlug,
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => $this->apiKey,
        ]);

        return $response->successful();
    }
}
