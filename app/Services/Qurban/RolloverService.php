<?php

namespace App\Services\Qurban;

use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolloverService
{
    /**
     * Execute period close (tutup buku) and create a new period.
     *
     * 1. Close current active period.
     * 2. Create new period from provided data.
     * 3. Clone all shohibuls to new period:
     *    - Lunas: reset collected_amount to 0, clear group.
     *    - Belum lunas: carry over collected_amount, clear group.
     *    - Both: update target_amount to new price.
     */
    public function execute(array $newPeriodData): QurbanPeriod
    {
        return DB::transaction(function () use ($newPeriodData) {
            // 1. Get and close current active period
            $oldPeriod = QurbanPeriod::active()->firstOrFail();
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

            // 3. Clone shohibuls from old period
            $shohibuls = Shohibul::where('period_id', $oldPeriod->id)->get();
            $cloned = 0;

            foreach ($shohibuls as $shohibul) {
                $isLunas = $shohibul->collected_amount >= $shohibul->target_amount;

                // Determine new target based on type
                $newTarget = $shohibul->target_type === 'sapi'
                    ? $newPeriod->sapi_price_per_slot
                    : $newPeriod->kambing_price;

                Shohibul::create([
                    'period_id' => $newPeriod->id,
                    'animal_group_id' => null, // Reset group assignment
                    'name' => $shohibul->name,
                    'phone' => $shohibul->phone,
                    'address' => $shohibul->address,
                    'target_type' => $shohibul->target_type,
                    'target_amount' => $newTarget,
                    'collected_amount' => $isLunas ? 0 : $shohibul->collected_amount,
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
