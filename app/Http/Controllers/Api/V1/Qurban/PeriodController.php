<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qurban\StorePeriodRequest;
use App\Models\Qurban\QurbanPeriod;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PeriodController extends Controller
{
    use ApiResponse;

    /**
     * Get the active period configuration (public).
     */
    public function active(): JsonResponse
    {
        $period = QurbanPeriod::active()->first();

        if (! $period) {
            return $this->errorResponse('Tidak ada periode qurban yang aktif saat ini.', 404);
        }

        return $this->successResponse($period);
    }

    /**
     * Create a new period (admin).
     */
    public function store(StorePeriodRequest $request): JsonResponse
    {
        Gate::authorize('qurban.periode.create');

        $data = $request->validated();

        // If setting as active, deactivate others first
        if ($request->boolean('is_active', false)) {
            QurbanPeriod::where('is_active', true)->update(['is_active' => false]);
            $data['is_active'] = true;
        }

        $period = QurbanPeriod::create($data);

        return $this->createdResponse($period, 'Periode qurban berhasil dibuat.');
    }

    /**
     * Update the active period configuration (admin).
     */
    public function update(StorePeriodRequest $request): JsonResponse
    {
        Gate::authorize('qurban.periode.update');

        $period = QurbanPeriod::active()->firstOrFail();
        $oldSapiPrice = $period->sapi_price_per_slot;
        $oldKambingPrice = $period->kambing_price;

        $period->update($request->validated());

        // If prices changed, update all shohibul targets in this period
        if ($period->sapi_price_per_slot != $oldSapiPrice) {
            $period->shohibuls()
                ->where('target_type', 'sapi')
                ->update(['target_amount' => $period->sapi_price_per_slot]);
        }

        if ($period->kambing_price != $oldKambingPrice) {
            $period->shohibuls()
                ->where('target_type', 'kambing')
                ->update(['target_amount' => $period->kambing_price]);
        }

        return $this->successResponse($period, 'Konfigurasi periode berhasil diperbarui.');
    }

    /**
     * List all periods (admin — for history).
     */
    public function index(): JsonResponse
    {
        Gate::authorize('qurban.periode.view');

        $periods = QurbanPeriod::orderByDesc('created_at')->get();

        return $this->successResponse($periods);
    }
}
