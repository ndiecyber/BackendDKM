<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Service;
use App\Services\ImageUploadService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

#[Group('Profil Web - Layanan')]
class ServiceController extends Controller
{
    use ApiResponse;

    /**
     * List all services.
     *
     * Supports optional filtering by category and active status.
     */
    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return $this->successResponse($services);
    }

    /**
     * Create a new service.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:255',
            'bg_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'description' => 'nullable|string',
            'details' => 'nullable|array',
            'details.fullDescription' => 'nullable|string',
            'details.schedule' => 'nullable|string',
            'details.location' => 'nullable|string',
            'details.supervisor' => 'nullable|string',
            'details.supervisorImage' => 'nullable|string',
            'details.supervisorWa' => 'nullable|string',
            'details.requirements' => 'nullable|array',
            'details.requirements.*' => 'string',
            'details.staff' => 'nullable|array',
            'details.staff.*.name' => 'required|string',
            'details.staff.*.role' => 'nullable|string',
            'details.staff.*.image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Handle bg_image upload
        if ($request->hasFile('bg_image')) {
            $path = ImageUploadService::storeAsWebp($request->file('bg_image'), 'services', 'public');
            $validated['bg_image'] = Storage::url($path);
        }

        $validated['sort_order'] = Service::max('sort_order') + 1;

        $service = Service::create($validated);

        return $this->createdResponse($service, 'Service created successfully.');
    }

    /**
     * Show a single service.
     */
    public function show(string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        return $this->successResponse($service);
    }

    /**
     * Update an existing service.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:255',
            'bg_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'description' => 'nullable|string',
            'details' => 'nullable|array',
            'details.fullDescription' => 'nullable|string',
            'details.schedule' => 'nullable|string',
            'details.location' => 'nullable|string',
            'details.supervisor' => 'nullable|string',
            'details.supervisorImage' => 'nullable|string',
            'details.supervisorWa' => 'nullable|string',
            'details.requirements' => 'nullable|array',
            'details.requirements.*' => 'string',
            'details.staff' => 'nullable|array',
            'details.staff.*.name' => 'required|string',
            'details.staff.*.role' => 'nullable|string',
            'details.staff.*.image' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Handle bg_image upload
        if ($request->hasFile('bg_image')) {
            // Delete old image
            if ($service->bg_image) {
                $oldPath = str_replace('/storage/', '', $service->bg_image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = ImageUploadService::storeAsWebp($request->file('bg_image'), 'services', 'public');
            $validated['bg_image'] = Storage::url($path);
        }

        $service->update($validated);

        return $this->successResponse($service, 'Service updated successfully.');
    }

    /**
     * Delete a service.
     */
    public function destroy(string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        // Delete bg_image file if present
        if ($service->bg_image) {
            $path = str_replace('/storage/', '', $service->bg_image);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $service->delete();

        return $this->successResponse(null, 'Service deleted successfully.');
    }
}
