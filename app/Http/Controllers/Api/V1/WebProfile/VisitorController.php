<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Visitor;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

#[Group('Profil Web - Ringkasan & Statistik')]
class VisitorController extends Controller
{
    use ApiResponse;

    /**
     * Record a new visitor hit from frontend.
     */
    public function store(Request $request): JsonResponse
    {
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent');
        $today = Carbon::now()->toDateString();

        // Check if this IP already visited today to prevent spam
        $exists = Visitor::where('ip_address', $ip)
            ->where('visited_date', $today)
            ->exists();

        if (! $exists) {
            Visitor::create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'visited_date' => $today,
            ]);
        }

        return $this->successResponse(null, 'Visitor logged successfully.');
    }
}
