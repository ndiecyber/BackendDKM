<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jamaah\StoreJamaahRequest;
use App\Http\Requests\Jamaah\UpdateJamaahRequest;
use App\Models\Jamaah;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Manajemen Jamaah')]
class JamaahController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('jamaah.view');

        $jamaah = Jamaah::query()
            ->search($request->search)
            ->with('user:id,name,email')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($jamaah);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJamaahRequest $request): JsonResponse
    {
        $jamaah = Jamaah::create($request->validated());

        return $this->createdResponse($jamaah, 'Jamaah created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('jamaah.view');

        $jamaah = Jamaah::with('user:id,name,email')->findOrFail($id);

        return $this->successResponse($jamaah);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJamaahRequest $request, string $id): JsonResponse
    {
        $jamaah = Jamaah::findOrFail($id);

        $jamaah->update($request->validated());

        return $this->successResponse($jamaah, 'Jamaah updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('jamaah.delete');

        $jamaah = Jamaah::findOrFail($id);
        $jamaah->delete();

        return $this->successResponse(null, 'Jamaah deleted successfully.');
    }

    /**
     * Restore a soft-deleted jamaah.
     */
    public function restore(string $id): JsonResponse
    {
        Gate::authorize('jamaah.delete');

        $jamaah = Jamaah::withTrashed()->findOrFail($id);

        if (! $jamaah->trashed()) {
            return $this->errorResponse('Jamaah is not deleted.', 400);
        }

        $jamaah->restore();

        return $this->successResponse(null, 'Jamaah restored successfully.');
    }
}
