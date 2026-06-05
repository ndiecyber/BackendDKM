<?php

namespace App\Services;

use App\Models\BankKas;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create an income transaction.
     */
    public function createIncome(array $data, array $attachments = []): Transaction
    {
        return DB::transaction(function () use ($data, $attachments) {
            $data['tipe'] = 'pemasukan';
            $data['nomor_transaksi'] = $this->generateNomorTransaksi('pemasukan', $data['tanggal']);

            $transaction = Transaction::create($data);

            $this->storeAttachments($transaction, $attachments);

            if ($transaction->status === 'approved') {
                $this->recalculateSaldo($transaction);
            }

            return $transaction->load('attachments');
        });
    }

    /**
     * Create an expense transaction.
     */
    public function createExpense(array $data, array $attachments = []): Transaction
    {
        return DB::transaction(function () use ($data, $attachments) {
            $data['tipe'] = 'pengeluaran';
            $data['nomor_transaksi'] = $this->generateNomorTransaksi('pengeluaran', $data['tanggal']);

            $transaction = Transaction::create($data);

            $this->storeAttachments($transaction, $attachments);

            if ($transaction->status === 'approved') {
                $this->recalculateSaldo($transaction);
            }

            return $transaction->load('attachments');
        });
    }

    /**
     * Create a transfer transaction.
     */
    public function createTransfer(array $data, array $attachments = []): Transaction
    {
        return DB::transaction(function () use ($data, $attachments) {
            $data['tipe'] = 'transfer';
            $data['nomor_transaksi'] = $this->generateNomorTransaksi('transfer', $data['tanggal']);

            $transaction = Transaction::create($data);

            $this->storeAttachments($transaction, $attachments);

            if ($transaction->status === 'approved') {
                $this->recalculateSaldo($transaction);
            }

            return $transaction->load('attachments');
        });
    }

    /**
     * Update an existing transaction (only if not approved).
     */
    public function updateTransaction(Transaction $transaction, array $data, array $attachments = []): Transaction
    {
        return DB::transaction(function () use ($transaction, $data, $attachments) {
            $wasApproved = $transaction->status === 'approved';

            $transaction->update($data);

            if (! empty($attachments)) {
                $this->storeAttachments($transaction, $attachments);
            }

            // Recalculate affected bank/kas saldo
            if ($wasApproved || $transaction->status === 'approved') {
                $this->recalculateAffectedBankKas($transaction);
            }

            return $transaction->load('attachments');
        });
    }

    /**
     * Update transaction status with saldo recalculation.
     */
    public function updateStatus(Transaction $transaction, string $newStatus): Transaction
    {
        return DB::transaction(function () use ($transaction, $newStatus) {
            $oldStatus = $transaction->status;
            $transaction->update(['status' => $newStatus]);

            // Recalculate saldo when status changes to/from approved
            if ($oldStatus !== $newStatus && ($oldStatus === 'approved' || $newStatus === 'approved')) {
                $this->recalculateAffectedBankKas($transaction);
            }

            return $transaction;
        });
    }

    /**
     * Soft delete a transaction and revert saldo if it was approved.
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $wasApproved = $transaction->status === 'approved';

            $transaction->delete();

            if ($wasApproved) {
                $this->recalculateAffectedBankKas($transaction);
            }
        });
    }

    /**
     * Restore a soft-deleted transaction and recalculate saldo if approved.
     */
    public function restoreTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->restore();

            if ($transaction->status === 'approved') {
                $this->recalculateAffectedBankKas($transaction);
            }
        });
    }

    /**
     * Generate auto-incrementing transaction number.
     * Format: PREFIX-YYYYMMDD-XXXX
     */
    public function generateNomorTransaksi(string $tipe, string $tanggal): string
    {
        $prefix = match ($tipe) {
            'pemasukan' => 'IN',
            'pengeluaran' => 'OUT',
            'transfer' => 'TRF',
        };

        $dateStr = date('Ymd', strtotime($tanggal));
        $pattern = "{$prefix}-{$dateStr}-";

        // Count existing transactions with this prefix and date to determine next sequence
        $count = Transaction::withTrashed()
            ->where('nomor_transaksi', 'like', $pattern.'%')
            ->count();

        $sequence = $count + 1;

        return $pattern.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Store file attachments for a transaction.
     */
    private function storeAttachments(Transaction $transaction, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('transactions/attachments', 'public');

            TransactionAttachment::create([
                'transaction_id' => $transaction->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    /**
     * Recalculate saldo for bank/kas accounts affected by a transaction.
     */
    private function recalculateSaldo(Transaction $transaction): void
    {
        $this->recalculateAffectedBankKas($transaction);
    }

    /**
     * Recalculate saldo for all bank/kas accounts related to a transaction.
     */
    private function recalculateAffectedBankKas(Transaction $transaction): void
    {
        if ($transaction->bank_kas_asal_id) {
            $bankKasAsal = BankKas::find($transaction->bank_kas_asal_id);
            $bankKasAsal?->hitungSaldoTerkini();
        }

        if ($transaction->bank_kas_tujuan_id) {
            $bankKasTujuan = BankKas::find($transaction->bank_kas_tujuan_id);
            $bankKasTujuan?->hitungSaldoTerkini();
        }
    }
}
