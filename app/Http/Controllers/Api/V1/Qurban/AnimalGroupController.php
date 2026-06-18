<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qurban\MoveMemberRequest;
use App\Models\Qurban\AnimalGroup;
use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnimalGroupController extends Controller
{
    use ApiResponse;

    /**
     * List all groups with members for active period (public).
     */
    public function index(): JsonResponse
    {
        $period = QurbanPeriod::active()->first();

        if (! $period) {
            return $this->errorResponse('Tidak ada periode aktif.', 404);
        }

        $groups = AnimalGroup::where('period_id', $period->id)
            ->with(['shohibuls:id,animal_group_id,name,phone,collected_amount,target_amount'])
            ->withCount('shohibuls')
            ->orderBy('target_type')
            ->orderBy('name')
            ->get();

        return $this->successResponse($groups);
    }

    /**
     * Create a new group manually (admin).
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('qurban.kelompok.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'target_type' => ['required', 'in:sapi,kambing'],
        ]);

        $period = QurbanPeriod::active()->firstOrFail();

        $group = AnimalGroup::create([
            'period_id' => $period->id,
            'name' => $validated['name'],
            'target_type' => $validated['target_type'],
        ]);

        return $this->createdResponse($group, 'Kelompok berhasil dibuat.');
    }

    /**
     * Move a shohibul to a different group (admin).
     */
    public function moveMember(MoveMemberRequest $request): JsonResponse
    {
        Gate::authorize('qurban.kelompok.update');

        $shohibul = Shohibul::findOrFail($request->shohibul_id);
        $newGroup = AnimalGroup::withCount('shohibuls')->findOrFail($request->new_group_id);

        // Validate: type must match
        if ($shohibul->target_type !== $newGroup->target_type) {
            return $this->errorResponse(
                'Jenis hewan shohibul tidak sesuai dengan kelompok tujuan.',
                422
            );
        }

        // Validate: sapi group max 7
        if ($newGroup->target_type === 'sapi' && $newGroup->shohibuls_count >= 7) {
            return $this->errorResponse(
                'Kelompok sapi tujuan sudah penuh (maksimal 7 anggota).',
                422
            );
        }

        $shohibul->update(['animal_group_id' => $newGroup->id]);

        return $this->successResponse(
            $shohibul->load('animalGroup:id,name,target_type'),
            'Shohibul berhasil dipindahkan ke kelompok '.$newGroup->name.'.'
        );
    }
}
