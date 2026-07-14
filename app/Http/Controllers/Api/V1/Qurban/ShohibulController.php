<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qurban\RegisterShohibulRequest;
use App\Http\Requests\Qurban\UpdateShohibulRequest;
use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use App\Services\Qurban\GroupingService;
use App\Services\Qurban\QurbanTransactionService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Qurban - Shohibul')]
class ShohibulController extends Controller
{
    use ApiResponse;

    public function __construct(
        private GroupingService $groupingService,
        private QurbanTransactionService $transactionService
    ) {}

    /**
     * List all shohibuls in active period (public).
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->has('period_id')
            ? QurbanPeriod::find($request->period_id)
            : QurbanPeriod::active()->first();

        if (! $period) {
            return $this->errorResponse('Tidak ada periode aktif.', 404);
        }

        $shohibuls = Shohibul::where('period_id', $period->id)
            ->search($request->search)
            ->byStatus($request->status)
            ->byType($request->type)
            ->with(['animalGroup:id,name,target_type', 'transactions' => function ($q) {
                $q->where('status', 'pending');
            }])
            ->orderBy('name')
            ->get();

        return $this->successResponse($shohibuls);
    }

    /**
     * Lightweight search for autocomplete (public).
     */
    public function search(Request $request): JsonResponse
    {
        $period = QurbanPeriod::active()->first();

        if (! $period || ! $request->q) {
            return $this->successResponse([]);
        }

        $results = Shohibul::where('period_id', $period->id)
            ->search($request->q)
            ->select('id', 'name', 'phone', 'address')
            ->limit(10)
            ->get();

        return $this->successResponse($results);
    }

    /**
     * Register new shohibul + create initial deposit (public).
     */
    public function register(RegisterShohibulRequest $request): JsonResponse
    {
        $period = QurbanPeriod::active()->first();

        if (! $period) {
            return $this->errorResponse('Tidak ada periode aktif.', 404);
        }

        if (now()->startOfDay()->gt($period->deadline_date)) {
            return $this->errorResponse('Pendaftaran untuk periode ini telah ditutup.', 422);
        }

        $data = $request->validated();

        // Prevent public users from using admin-only payment methods
        if (in_array($data['payment_method'], ['tunai']) && ! auth('sanctum')->check()) {
            return $this->errorResponse('Metode pembayaran tunai hanya dapat dilakukan oleh pengurus/admin.', 403);
        }

        // Check for duplicate (name + phone)
        $existing = Shohibul::where('period_id', $period->id)
            ->where('name', $data['name'])
            ->where('phone', $data['phone'])
            ->first();

        if ($existing) {
            return $this->errorResponse(
                'Peserta dengan nama dan nomor telepon yang sama sudah terdaftar.',
                422
            );
        }

        // Determine target amount
        $targetAmount = $data['target_type'] === 'sapi'
            ? $period->sapi_price_per_slot
            : $period->kambing_price;

        // Validate initial amount doesn't exceed target
        if ($data['initial_amount'] > $targetAmount) {
            return $this->errorResponse(
                'Setoran awal tidak boleh melebihi harga hewan (Rp '.number_format($targetAmount, 0, ',', '.').').',
                422
            );
        }

        // Auto-assign group
        $group = $this->groupingService->assignGroup($period, $data['target_type']);

        // Create shohibul
        $shohibul = Shohibul::create([
            'period_id' => $period->id,
            'animal_group_id' => $group->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'target_type' => $data['target_type'],
            'target_amount' => $targetAmount,
            'collected_amount' => 0,
        ]);

        // Create initial deposit
        try {
            $result = $this->transactionService->createDeposit(
                $shohibul,
                $data['initial_amount'],
                $data['payment_method'],
                $request->file('payment_proof')
            );
        } catch (\Exception $e) {
            // Rollback shohibul if payment creation fails
            $shohibul->forceDelete();

            return $this->errorResponse($e->getMessage(), 500);
        }

        $shohibul->load('animalGroup:id,name,target_type');

        return $this->createdResponse([
            'shohibul' => $shohibul,
            'transaction' => $result['transaction'],
            'payment' => $result['payment'],
        ], 'Pendaftaran berhasil. Silakan lakukan pembayaran.');
    }

    /**
     * Show shohibul detail + transaction history (public).
     */
    public function show(string $id): JsonResponse
    {
        $shohibul = Shohibul::with([
            'animalGroup:id,name,target_type',
            'transactions' => fn ($q) => $q->orderByDesc('created_at'),
        ])->findOrFail($id);

        return $this->successResponse($shohibul);
    }

    /**
     * Update shohibul identity (admin).
     */
    public function update(UpdateShohibulRequest $request, string $id): JsonResponse
    {
        Gate::authorize('qurban.shohibul.update');

        $shohibul = Shohibul::findOrFail($id);
        $data = $request->validated();

        if (isset($data['target_type']) && $data['target_type'] !== $shohibul->target_type) {
            $period = QurbanPeriod::findOrFail($shohibul->period_id);
            $data['target_amount'] = $data['target_type'] === 'sapi'
                ? $period->sapi_price_per_slot
                : $period->kambing_price;

            // Jika pindah tipe, keluarkan dari kelompok hewan lama
            $data['animal_group_id'] = null;
        }

        $shohibul->update($data);

        return $this->successResponse($shohibul, 'Data shohibul berhasil diperbarui.');
    }

    /**
     * Soft delete shohibul (admin).
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('qurban.shohibul.delete');

        $shohibul = Shohibul::findOrFail($id);

        // Warn if has balance or pending transactions
        if ($shohibul->collected_amount > 0 || $shohibul->hasPendingTransaction()) {
            return $this->errorResponse(
                'Shohibul memiliki saldo atau transaksi pending. Pastikan sudah diselesaikan sebelum menghapus.',
                422
            );
        }

        // Hapus gambar bukti pembayaran dari storage sebelum hard delete
        $proofs = $shohibul->transactions()->whereNotNull('payment_proof_path')->pluck('payment_proof_path');
        if ($proofs->isNotEmpty()) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($proofs->toArray());
        }

        $shohibul->forceDelete();

        return $this->successResponse(null, 'Shohibul berhasil dihapus permanen beserta data transaksinya.');
    }
}
