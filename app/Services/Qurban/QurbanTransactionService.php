<?php

namespace App\Services\Qurban;

use App\Models\Qurban\QurbanTransaction;
use App\Models\Qurban\Shohibul;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QurbanTransactionService
{
    public function __construct(
        private PaKasirService $paKasir
    ) {}

    /**
     * Create a deposit transaction and request payment from PaKasir.
     *
     * @return array{transaction: QurbanTransaction, payment: array}
     */
    public function createDeposit(Shohibul $shohibul, int $amount, string $paymentMethod): array
    {
        // Generate unique order_id
        $orderId = 'QRB-'.now()->format('ymd').'-'.strtoupper(Str::random(6));

        return DB::transaction(function () use ($shohibul, $amount, $paymentMethod, $orderId) {
            // Create transaction record
            $transaction = QurbanTransaction::create([
                'shohibul_id' => $shohibul->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
            ]);

            if (in_array($paymentMethod, ['tunai', 'transfer'])) {
                // Skip PaKasir for manual methods, admin will verify later
                $payment = [
                    'payment_number' => strtoupper($paymentMethod),
                    'total_payment' => $amount,
                    'expired_at' => now()->addDays(7)->toDateTimeString(),
                    'payment_method' => $paymentMethod,
                ];
            } else {
                // Request payment from PaKasir
                $payment = $this->paKasir->createTransaction($paymentMethod, $orderId, $amount);

                // Update transaction with PaKasir response
                $transaction->update([
                    'payment_number' => $payment['payment_number'],
                    'total_payment' => $payment['total_payment'],
                    'expired_at' => $payment['expired_at'],
                ]);
            }

            return [
                'transaction' => $transaction->fresh(),
                'payment' => $payment,
            ];
        });
    }

    /**
     * Handle webhook callback from PaKasir.
     */
    public function handleWebhook(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? null;
        $status = $payload['status'] ?? null;
        $amount = $payload['amount'] ?? null;

        if (! $orderId || ! $status) {
            Log::warning('PaKasir webhook: missing order_id or status', $payload);

            return false;
        }

        $transaction = QurbanTransaction::where('order_id', $orderId)->first();

        if (! $transaction) {
            Log::warning('PaKasir webhook: transaction not found', ['order_id' => $orderId]);

            return false;
        }

        // Idempotency check: already processed
        if ($transaction->status === 'success') {
            Log::info('PaKasir webhook: duplicate — already success', ['order_id' => $orderId]);

            return true;
        }

        // Double-check via PaKasir API
        $detail = $this->paKasir->getTransactionDetail($orderId, (int) $transaction->amount);
        if ($detail && ($detail['status'] ?? '') !== 'completed') {
            Log::warning('PaKasir webhook: double-check status mismatch', [
                'order_id' => $orderId,
                'webhook_status' => $status,
                'api_status' => $detail['status'] ?? 'unknown',
            ]);

            return false;
        }

        if ($status === 'completed') {
            return $this->markSuccess($transaction, $payload['completed_at'] ?? now());
        }

        // Failed or expired
        $transaction->update(['status' => 'failed']);

        return true;
    }

    /**
     * Mark transaction as success and add amount to shohibul balance.
     */
    private function markSuccess(QurbanTransaction $transaction, $completedAt): bool
    {
        return DB::transaction(function () use ($transaction, $completedAt) {
            $transaction->update([
                'status' => 'success',
                'completed_at' => $completedAt,
            ]);

            $shohibul = $transaction->shohibul;
            $shohibul->increment('collected_amount', $transaction->amount);
            $shohibul->update([
                'last_payment_month' => now()->format('Y-m'),
            ]);

            Log::info('Qurban payment success', [
                'order_id' => $transaction->order_id,
                'shohibul_id' => $shohibul->id,
                'amount' => $transaction->amount,
                'new_balance' => $shohibul->fresh()->collected_amount,
            ]);

            return true;
        });
    }

    /**
     * Admin: record a cash deposit (bypasses PaKasir).
     */
    public function manualDeposit(Shohibul $shohibul, int $amount): QurbanTransaction
    {
        $orderId = 'TUNAI-'.now()->format('ymd').'-'.strtoupper(Str::random(6));

        return DB::transaction(function () use ($shohibul, $amount, $orderId) {
            $transaction = QurbanTransaction::create([
                'shohibul_id' => $shohibul->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => 'success',
                'payment_method' => 'tunai',
                'total_payment' => $amount,
                'completed_at' => now(),
            ]);

            $shohibul->increment('collected_amount', $amount);
            $shohibul->update([
                'last_payment_month' => now()->format('Y-m'),
            ]);

            return $transaction;
        });
    }

    /**
     * Admin: Refund/Tarik Kelebihan Dana.
     */
    public function refund(Shohibul $shohibul, int $amount): QurbanTransaction
    {
        $orderId = 'REFUND-'.now()->format('ymd').'-'.strtoupper(Str::random(6));

        return DB::transaction(function () use ($shohibul, $amount, $orderId) {
            // Kita simpan amount sebagai nilai negatif
            $transaction = QurbanTransaction::create([
                'shohibul_id' => $shohibul->id,
                'order_id' => $orderId,
                'amount' => -$amount,
                'status' => 'success',
                'payment_method' => 'refund',
                'total_payment' => -$amount,
                'completed_at' => now(),
            ]);

            // Decrement the collected amount
            $shohibul->decrement('collected_amount', $amount);
            
            Log::info('Qurban overpayment refunded', [
                'order_id' => $orderId,
                'shohibul_id' => $shohibul->id,
                'refund_amount' => $amount,
                'new_balance' => $shohibul->fresh()->collected_amount,
            ]);

            return $transaction;
        });
    }

    /**
     * Admin: cancel a transaction.
     */
    public function cancelTransaction(QurbanTransaction $transaction): QurbanTransaction
    {
        if ($transaction->status === 'cancelled') {
            throw new \Exception('Transaksi ini sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($transaction) {
            // Jika transaksi sukses, kurangi saldo shohibul
            if ($transaction->status === 'success') {
                $shohibul = $transaction->shohibul;
                $shohibul->decrement('collected_amount', $transaction->amount);
                
                // Tambahkan catatan log
                Log::info('Qurban payment voided (cancelled after success)', [
                    'order_id' => $transaction->order_id,
                    'shohibul_id' => $shohibul->id,
                    'amount' => $transaction->amount,
                    'new_balance' => $shohibul->fresh()->collected_amount,
                ]);
            }

            // Jika status pending dan bukan manual/tunai/transfer, batalkan di PaKasir
            if ($transaction->status === 'pending' && !in_array($transaction->payment_method, ['tunai', 'transfer'])) {
                try {
                    $this->paKasir->cancelTransaction($transaction->order_id, (int) $transaction->amount);
                } catch (\Exception $e) {
                    Log::warning('Gagal membatalkan transaksi di PaKasir: ' . $e->getMessage());
                }
            }

            $transaction->update(['status' => 'cancelled']);

            return $transaction;
        });
    }

    /**
     * Admin: manually verify a pending transaction.
     */
    public function verifyTransaction(QurbanTransaction $transaction): QurbanTransaction
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Hanya transaksi pending yang bisa diverifikasi.');
        }

        $this->markSuccess($transaction, now());

        return $transaction;
    }
}
