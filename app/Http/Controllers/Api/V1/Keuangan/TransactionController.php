<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\StoreTransactionRequest;
use App\Http\Requests\Keuangan\UpdateTransactionRequest;
use App\Http\Requests\Keuangan\UpdateTransactionStatusRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Keuangan - Transaksi')]
class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        $transactions = Transaction::query()
            ->search($request->search)
            ->byTipe($request->tipe)
            ->byStatus($request->status)
            ->byCategory($request->category_id)
            ->byBankKas($request->bank_kas_id)
            ->byProgram($request->program_id)
            ->byDateRange($request->tanggal_mulai, $request->tanggal_akhir)
            ->with(['category:id,nama', 'program:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama', 'jamaah:id,nama_lengkap', 'createdBy:id,name'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($transactions);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['tanggal'] = $data['tanggal'] ?? now()->toDateString();

        $attachments = $request->file('attachments', []);

        $transaction = match ($data['tipe']) {
            'pemasukan' => $this->transactionService->createIncome($data, $attachments),
            'pengeluaran' => $this->transactionService->createExpense($data, $attachments),
            'transfer' => $this->transactionService->createTransfer($data, $attachments),
        };

        $transaction->load(['category:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama', 'jamaah:id,nama_lengkap', 'createdBy:id,name']);

        return $this->createdResponse($transaction, 'Transaksi berhasil dicatat.');
    }

    /**
     * Display the specified transaction.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        $transaction = Transaction::with([
            'category', 'bankKasAsal', 'bankKasTujuan',
            'jamaah', 'createdBy:id,name', 'attachments',
        ])->findOrFail($id);

        return $this->successResponse($transaction);
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, string $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->status === 'approved') {
            return $this->errorResponse('Transaksi yang sudah disetujui tidak dapat diubah.', 422);
        }

        $data = $request->validated();
        $attachments = $request->file('attachments', []);

        $transaction = $this->transactionService->updateTransaction($transaction, $data, $attachments);
        $transaction->load(['category:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama', 'jamaah:id,nama_lengkap', 'createdBy:id,name']);

        return $this->successResponse($transaction, 'Transaksi berhasil diperbarui.');
    }

    /**
     * Soft delete the specified transaction.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.delete');

        $transaction = Transaction::findOrFail($id);
        $this->transactionService->deleteTransaction($transaction);

        return $this->successResponse(null, 'Transaksi berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted transaction.
     */
    public function restore(string $id): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.delete');

        $transaction = Transaction::withTrashed()->findOrFail($id);

        if (! $transaction->trashed()) {
            return $this->errorResponse('Transaksi tidak dalam kondisi terhapus.', 400);
        }

        $this->transactionService->restoreTransaction($transaction);

        return $this->successResponse(null, 'Transaksi berhasil dipulihkan.');
    }

    /**
     * Update transaction status (draft → pending → approved).
     */
    public function updateStatus(UpdateTransactionStatusRequest $request, string $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        // Validate status transition
        $validTransitions = [
            'draft' => ['pending', 'approved'],
            'pending' => ['draft', 'approved'],
            'approved' => ['pending'],
        ];

        $allowedStatuses = $validTransitions[$transaction->status] ?? [];

        if (! in_array($request->status, $allowedStatuses)) {
            return $this->errorResponse(
                "Status tidak dapat diubah dari '{$transaction->status}' ke '{$request->status}'.",
                422
            );
        }

        $transaction = $this->transactionService->updateStatus($transaction, $request->status);
        $transaction->load(['category:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama']);

        return $this->successResponse($transaction, 'Status transaksi berhasil diperbarui.');
    }

    /**
     * Display a listing of soft-deleted transactions.
     */
    public function trashed(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        $transactions = Transaction::onlyTrashed()
            ->with(['category:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama', 'program:id,nama'])
            ->orderBy('deleted_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($transactions);
    }

    /**
     * Force delete a soft-deleted transaction permanently.
     */
    public function forceDelete(string $id): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.delete');

        $transaction = Transaction::onlyTrashed()->findOrFail($id);
        $transaction->forceDelete();

        return $this->successResponse(null, 'Transaksi berhasil dihapus permanen.');
    }
}
