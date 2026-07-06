<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qurban\DepositRequest;
use App\Http\Requests\Qurban\ManualDepositRequest;
use App\Models\Qurban\QurbanTransaction;
use App\Models\Qurban\Shohibul;
use App\Services\Qurban\QurbanTransactionService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Qurban - Transaksi')]
class QurbanTransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QurbanTransactionService $transactionService
    ) {}

    /**
     * List all transactions (public, supports filters).
     */
    public function index(Request $request): JsonResponse
    {
        $transactions = QurbanTransaction::query()
            ->when($request->period_id, function ($q, $periodId) {
                $q->whereHas('shohibul', fn ($sq) => $sq->where('period_id', $periodId));
            })
            ->when($request->search, function ($q, $search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('shohibul', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"));
            })
            ->byStatus($request->status)
            ->byMethod($request->payment_method)
            ->byDateRange($request->date_from, $request->date_to)
            ->with('shohibul:id,name,phone,address,target_type')
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return $this->successResponse($transactions);
    }

    /**
     * Create a follow-up deposit for existing shohibul (public).
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        $shohibul = Shohibul::with('period')->findOrFail($request->shohibul_id);

        // Check: Deadline
        if ($shohibul->period && now()->startOfDay()->gt($shohibul->period->deadline_date)) {
            return $this->errorResponse('Masa penyetoran untuk periode ini telah ditutup.', 422);
        }

        // Check: already lunas
        if ($shohibul->is_lunas) {
            return $this->errorResponse('Shohibul sudah lunas, tidak bisa melakukan setoran.', 422);
        }

        // Check: has pending transaction
        if ($shohibul->hasPendingTransaction()) {
            $pending = $shohibul->pendingTransaction();

            return $this->errorResponse(
                'Shohibul masih memiliki tagihan pending. Selesaikan pembayaran sebelumnya terlebih dahulu.',
                422,
                ['pending_transaction' => $pending]
            );
        }

        // Check: amount <= remaining
        $remaining = $shohibul->target_amount - $shohibul->collected_amount;
        if ($request->amount > $remaining) {
            return $this->errorResponse(
                'Setoran melebihi sisa kekurangan (Rp '.number_format($remaining, 0, ',', '.').').',
                422
            );
        }

        try {
            $result = $this->transactionService->createDeposit(
                $shohibul,
                $request->amount,
                $request->payment_method
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->createdResponse([
            'transaction' => $result['transaction'],
            'payment' => $result['payment'],
        ], 'Setoran berhasil dibuat. Silakan lakukan pembayaran.');
    }

    /**
     * Admin: record a manual cash deposit.
     */
    public function manualDeposit(ManualDepositRequest $request): JsonResponse
    {
        Gate::authorize('qurban.transaksi.create');

        $shohibul = Shohibul::findOrFail($request->shohibul_id);

        if ($shohibul->is_lunas) {
            return $this->errorResponse('Shohibul sudah lunas.', 422);
        }

        $remaining = $shohibul->target_amount - $shohibul->collected_amount;
        if ($request->amount > $remaining) {
            return $this->errorResponse(
                'Setoran melebihi sisa kekurangan (Rp '.number_format($remaining, 0, ',', '.').').',
                422
            );
        }

        $transaction = $this->transactionService->manualDeposit($shohibul, $request->amount);

        return $this->createdResponse($transaction, 'Setoran tunai berhasil dicatat.');
    }

    public function cancel(string $id): JsonResponse
    {
        Gate::authorize('qurban.transaksi.cancel');

        $transaction = QurbanTransaction::findOrFail($id);

        try {
            $transaction = $this->transactionService->cancelTransaction($transaction);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(null, 'Transaksi berhasil dibatalkan.');
    }

    /**
     * Admin: manually verify a pending transaction.
     */
    public function verify(string $id): JsonResponse
    {
        // Using same authorize gate prefix
        Gate::authorize('qurban.transaksi.verify');

        $transaction = QurbanTransaction::findOrFail($id);

        try {
            $transaction = $this->transactionService->verifyTransaction($transaction);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($transaction, 'Transaksi berhasil diverifikasi.');
    }
    /**
     * Admin: Refund kelebihan bayar shohibul
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        Gate::authorize('qurban.transaksi.manage');

        $shohibul = Shohibul::findOrFail($id);
        
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $excess = $shohibul->collected_amount - $shohibul->target_amount;
        
        if ($excess <= 0) {
            return $this->errorResponse('Shohibul tidak memiliki kelebihan bayar.', 422);
        }

        if ($request->amount > $excess) {
            return $this->errorResponse('Nominal penarikan melebihi jumlah kelebihan bayar (Rp '.number_format($excess, 0, ',', '.').').', 422);
        }

        try {
            $transaction = $this->transactionService->refund($shohibul, $request->amount);
            
            return $this->successResponse($transaction, 'Kelebihan dana berhasil ditarik/direfund.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
