<?php

namespace App\Services\Qurban;

use App\Models\Qurban\AnimalGroup;
use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\QurbanTransaction;
use App\Models\Qurban\Shohibul;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolloverService
{
    public function __construct(
        private QurbanTransactionService $transactionService
    ) {}

    /**
     * Execute period close (tutup buku) and create a new period.
     *
     * 1. Get current active period.
     * 2. Cancel all pending transactions in the old period.
     * 3. Close current active period.
     * 4. Create new period from provided data.
     * 5. Clone all shohibuls to new period:
     *    - Lunas: reset collected_amount to 0, clear group.
     *    - Belum lunas: carry over collected_amount, clear group.
     *    - Both: update target_amount to new price.
     */
    public function execute(array $newPeriodData): QurbanPeriod
    {
        return DB::transaction(function () use ($newPeriodData) {
            // 1. Get current active period
            $oldPeriod = QurbanPeriod::active()->firstOrFail();

            // 2. Cancel all pending transactions in the old period
            $pendingTransactions = QurbanTransaction::whereHas('shohibul', function ($q) use ($oldPeriod) {
                $q->where('period_id', $oldPeriod->id);
            })->where('status', 'pending')->get();

            foreach ($pendingTransactions as $tx) {
                try {
                    $this->transactionService->cancelTransaction($tx);
                } catch (\Exception $e) {
                    Log::error('Rollover: failed to cancel transaction', ['order_id' => $tx->order_id, 'error' => $e->getMessage()]);
                }
            }

            // 3. Close current active period
            $oldPeriod->update(['is_active' => false]);

            Log::info('Rollover: closed period', ['period_id' => $oldPeriod->id, 'name' => $oldPeriod->name]);

            // 2. Create new period
            $newPeriod = QurbanPeriod::create([
                'name' => $newPeriodData['name'],
                'sapi_price_per_slot' => $newPeriodData['sapi_price_per_slot'],
                'kambing_price' => $newPeriodData['kambing_price'],
                'deadline_date' => $newPeriodData['deadline_date'],
                'is_active' => true,
            ]);

            Log::info('Rollover: created new period', ['period_id' => $newPeriod->id, 'name' => $newPeriod->name]);

            // 3. Clone animal groups
            $oldGroups = AnimalGroup::where('period_id', $oldPeriod->id)->get();
            $groupIdMap = [];
            foreach ($oldGroups as $oldGroup) {
                $newGroup = AnimalGroup::create([
                    'period_id' => $newPeriod->id,
                    'name' => $oldGroup->name,
                    'target_type' => $oldGroup->target_type,
                ]);
                $groupIdMap[$oldGroup->id] = $newGroup->id;
            }

            // 3. Clone shohibuls from old period
            $shohibuls = Shohibul::where('period_id', $oldPeriod->id)->get();
            $cloned = 0;

            foreach ($shohibuls as $shohibul) {
                $isLunas = $shohibul->collected_amount >= $shohibul->target_amount;
                $excess = max(0, $shohibul->collected_amount - $shohibul->target_amount);

                // Determine new target based on type
                $newTarget = $isLunas ? 0 : ($shohibul->target_type === 'sapi'
                    ? $newPeriod->sapi_price_per_slot
                    : $newPeriod->kambing_price);

                Shohibul::create([
                    'period_id' => $newPeriod->id,
                    'animal_group_id' => $isLunas ? null : ($shohibul->animal_group_id ? ($groupIdMap[$shohibul->animal_group_id] ?? null) : null),
                    'name' => $shohibul->name,
                    'phone' => $shohibul->phone,
                    'address' => $shohibul->address,
                    'target_type' => $isLunas ? null : $shohibul->target_type,
                    'target_amount' => $newTarget,
                    'collected_amount' => $isLunas ? $excess : $shohibul->collected_amount,
                    'last_payment_month' => $isLunas ? null : $shohibul->last_payment_month,
                ]);

                $cloned++;
            }

            Log::info('Rollover: cloned shohibuls', [
                'old_period' => $oldPeriod->id,
                'new_period' => $newPeriod->id,
                'total' => $cloned,
            ]);

            return $newPeriod;
        });
    }
}
