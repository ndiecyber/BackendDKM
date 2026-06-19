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
            ->byStatus($request->status)
            ->byMethod($request->payment_method)
            ->byDateRange($request->date_from, $request->date_to)
            ->with('shohibul:id,name,phone,target_type')
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

    /**
     * Admin: cancel a pending transaction.
     */
    public function cancel(string $id): JsonResponse
    {
        Gate::authorize('qurban.transaksi.cancel');

        $transaction = QurbanTransaction::findOrFail($id);

        try {
            $transaction = $this->transactionService->cancelTransaction($transaction);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($transaction, 'Transaksi berhasil dibatalkan.');
    }
}
