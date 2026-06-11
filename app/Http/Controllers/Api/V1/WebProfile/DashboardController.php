<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Announcement;
use App\Models\WebProfile\Event;
use App\Models\WebProfile\Gallery;
use App\Models\WebProfile\Visitor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get summary stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $totalEvents = Event::count();
        $totalAnnouncementsActive = Announcement::where('is_active', true)->count();
        $totalGalleries = Gallery::count();

        $visitorsThisMonth = Visitor::whereBetween('visited_date', [$startOfMonth, $endOfMonth])->count();

        return $this->successResponse([
            'total_events' => $totalEvents,
            'total_announcements_active' => $totalAnnouncementsActive,
            'total_galleries' => $totalGalleries,
            'visitors_this_month' => $visitorsThisMonth,
        ]);
    }

    /**
     * Get visitor stats for chart.
     */
    public function visitorsChart(Request $request): JsonResponse
    {
        $days = (int) ($request->days ?? 30);
        $startDate = Carbon::now()->subDays($days - 1)->toDateString();
        $endDate = Carbon::now()->toDateString();

        $data = Visitor::query()
            ->whereBetween('visited_date', [$startDate, $endDate])
            ->select('visited_date as date', DB::raw('count(*) as count'))
            ->groupBy('visited_date')
            ->orderBy('visited_date')
            ->get();

        // Fill missing dates
        $chartData = [];
        for ($i = 0; $i < $days; $i++) {
            $dateString = Carbon::now()->subDays($days - 1 - $i)->toDateString();
            $chartData[$dateString] = [
                'date' => $dateString,
                'count' => 0,
            ];
        }

        foreach ($data as $item) {
            $chartData[$item->date]['count'] = (int) $item->count;
        }

        return $this->successResponse(array_values($chartData));
    }
}
