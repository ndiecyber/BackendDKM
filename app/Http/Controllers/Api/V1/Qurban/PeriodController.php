<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qurban\RolloverRequest;
use App\Http\Requests\Qurban\StorePeriodRequest;
use App\Models\Qurban\QurbanPeriod;
use App\Services\Qurban\RolloverService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

#[Group('Qurban - Periode')]
class PeriodController extends Controller
{
    use ApiResponse;

    /**
     * Get the active period configuration (public).
     *
     * Digunakan untuk mengambil informasi periode qurban yang sedang aktif saat ini.
     * Endpoint ini dapat diakses secara publik tanpa otentikasi.
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
     *
     * Membuat konfigurasi periode qurban baru. Jika parameter `is_active` bernilai true,
     * sistem secara otomatis akan menonaktifkan periode lain dan mengaktifkan periode ini.
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
     *
     * Memperbarui konfigurasi harga dan nama untuk periode qurban yang sedang aktif saat ini.
     * Jika harga berubah, ini akan memperbarui target donasi (target_amount) pada shohibul terkait.
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
        
        $periods->map(function ($period) {
            $shohibuls = \App\Models\Qurban\Shohibul::where('period_id', $period->id)->get();
            $period->totalSapi = $shohibuls->where('target_type', 'sapi')->count();
            $period->sapiLunas = $shohibuls->where('target_type', 'sapi')->filter(fn($s) => $s->collected_amount >= $s->target_amount)->count();
            $period->sapiBelumLunas = $period->totalSapi - $period->sapiLunas;
            
            $period->totalKambing = $shohibuls->where('target_type', 'kambing')->count();
            $period->kambingLunas = $shohibuls->where('target_type', 'kambing')->filter(fn($s) => $s->collected_amount >= $s->target_amount)->count();
            $period->kambingBelumLunas = $period->totalKambing - $period->kambingLunas;
            
            $period->totalShohibul = $shohibuls->count();
            $period->totalDana = $shohibuls->sum('collected_amount');
            return $period;
        });

        return $this->successResponse($periods);
    }

    /**
     * Execute rollover / tutup buku (admin).
     */
    public function rollover(RolloverRequest $request, RolloverService $rolloverService): JsonResponse
    {
        Gate::authorize('qurban.rollover.execute');

        $validated = $request->validated();

        $newPeriod = $rolloverService->execute($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tutup buku berhasil. Periode baru telah diaktifkan.',
            'data' => $newPeriod,
        ], 201);
    }
}
