<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Models\Qurban\AnimalGroup;
use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\QurbanTransaction;
use App\Models\Qurban\Shohibul;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Qurban - Dashboard')]
class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get aggregated dashboard statistics (public).
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->has('period_id')
            ? QurbanPeriod::find($request->period_id)
            : QurbanPeriod::active()->first();

        if (! $period) {
            return $this->errorResponse('Tidak ada periode aktif.', 404);
        }

        $shohibuls = Shohibul::where('period_id', $period->id);

        $totalShohibul = (clone $shohibuls)->count();
        $totalCollected = (clone $shohibuls)->sum('collected_amount');
        $totalTarget = (clone $shohibuls)->sum('target_amount');
        $countLunas = (clone $shohibuls)->whereColumn('collected_amount', '>=', 'target_amount')->count();
        $countBelumLunas = $totalShohibul - $countLunas;

        // Animal counts
        $countSapiShohibul = (clone $shohibuls)->where('target_type', 'sapi')->count();
        $countKambingShohibul = (clone $shohibuls)->where('target_type', 'kambing')->count();
        $countSapiGroups = AnimalGroup::where('period_id', $period->id)->where('target_type', 'sapi')->count();

        // Percentage
        $percentage = $totalTarget > 0
            ? round(($totalCollected / $totalTarget) * 100, 1)
            : 0;

        // Recent transactions (5 latest successful)
        $recentTransactions = QurbanTransaction::whereHas('shohibul', fn ($q) => $q->where('period_id', $period->id))
            ->where('status', 'success')
            ->with('shohibul:id,name,target_type')
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();

        // Pending transactions count
        $pendingCount = QurbanTransaction::whereHas('shohibul', fn ($q) => $q->where('period_id', $period->id))
            ->where('status', 'pending')
            ->count();

        return $this->successResponse([
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'deadline_date' => $period->deadline_date->toDateString(),
                'days_remaining' => max(0, now()->diffInDays($period->deadline_date, false)),
            ],
            'summary' => [
                'total_shohibul' => $totalShohibul,
                'total_collected' => $totalCollected,
                'total_target' => $totalTarget,
                'count_lunas' => $countLunas,
                'count_belum_lunas' => $countBelumLunas,
                'percentage' => $percentage,
            ],
            'animals' => [
                'sapi_shohibul' => $countSapiShohibul,
                'kambing_shohibul' => $countKambingShohibul,
                'sapi_groups' => $countSapiGroups,
                'estimated_sapi' => $countSapiGroups, // Each full group = 1 sapi
            ],
            'pending_transactions' => $pendingCount,
            'recent_transactions' => $recentTransactions,
        ]);
    }
}
