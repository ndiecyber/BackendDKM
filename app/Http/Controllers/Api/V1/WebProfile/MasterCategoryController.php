<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\MasterCategory;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profil Web - Master Data')]
class MasterCategoryController extends Controller
{
    use ApiResponse;

    /**
     * List all master data categories.
     *
     * Supports filtering by type: kategori, tipe_berita, label, status.
     * Returns data grouped by type if no type filter is specified.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->type) {
            $categories = MasterCategory::ofType($request->type)
                ->orderBy('sort_order')
                ->get();

            return $this->successResponse($categories);
        }

        // Return grouped by type for the settings page
        $grouped = [
            'kategori' => MasterCategory::ofType('kategori')->orderBy('sort_order')->get(),
            'tipeBerita' => MasterCategory::ofType('tipe_berita')->orderBy('sort_order')->get(),
            'label' => MasterCategory::ofType('label')->orderBy('sort_order')->get(),
            'status' => MasterCategory::ofType('status')->orderBy('sort_order')->get(),
        ];

        return $this->successResponse($grouped);
    }

    /**
     * Create a new master data category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:kategori,tipe_berita,label,status',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon_name' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
        ]);

        $validated['sort_order'] = MasterCategory::ofType($validated['type'])->max('sort_order') + 1;

        $category = MasterCategory::create($validated);

        return $this->createdResponse($category, 'Master data created successfully.');
    }

    /**
     * Show a single master data category.
     */
    public function show(string $id): JsonResponse
    {
        $category = MasterCategory::findOrFail($id);

        return $this->successResponse($category);
    }

    /**
     * Update a master data category.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = MasterCategory::findOrFail($id);

        $validated = $request->validate([
            'type' => 'nullable|string|in:kategori,tipe_berita,label,status',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon_name' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($validated);

        return $this->successResponse($category, 'Master data updated successfully.');
    }

    /**
     * Delete a master data category.
     */
    public function destroy(string $id): JsonResponse
    {
        $category = MasterCategory::findOrFail($id);
        $category->delete();

        return $this->successResponse(null, 'Master data deleted successfully.');
    }

    /**
     * Bulk update master data.
     *
     * Accepts a full payload of all master data grouped by type and syncs them.
     * This is the pattern used by the frontend settings page.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => 'nullable|array',
            'kategori.*.id' => 'nullable|integer',
            'kategori.*.name' => 'required|string|max:255',
            'kategori.*.description' => 'nullable|string',
            'kategori.*.icon_name' => 'nullable|string|max:50',

            'tipeBerita' => 'nullable|array',
            'tipeBerita.*.id' => 'nullable|integer',
            'tipeBerita.*.name' => 'required|string|max:255',
            'tipeBerita.*.description' => 'nullable|string',
            'tipeBerita.*.color' => 'nullable|string|max:30',

            'label' => 'nullable|array',
            'label.*.id' => 'nullable|integer',
            'label.*.name' => 'required|string|max:255',
            'label.*.description' => 'nullable|string',
            'label.*.color' => 'nullable|string|max:30',

            'status' => 'nullable|array',
            'status.*.id' => 'nullable|integer',
            'status.*.name' => 'required|string|max:255',
            'status.*.description' => 'nullable|string',
            'status.*.color' => 'nullable|string|max:30',
        ]);

        $typeMapping = [
            'kategori' => 'kategori',
            'tipeBerita' => 'tipe_berita',
            'label' => 'label',
            'status' => 'status',
        ];

        foreach ($typeMapping as $key => $dbType) {
            if (!isset($validated[$key])) {
                continue;
            }

            $existingIds = [];

            foreach ($validated[$key] as $index => $itemData) {
                $data = [
                    'type' => $dbType,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'icon_name' => $itemData['icon_name'] ?? null,
                    'color' => $itemData['color'] ?? null,
                    'sort_order' => $index,
                ];

                if (!empty($itemData['id'])) {
                    $item = MasterCategory::find($itemData['id']);
                    if ($item && $item->type === $dbType) {
                        $item->update($data);
                        $existingIds[] = $item->id;
                    } else {
                        $item = MasterCategory::create($data);
                        $existingIds[] = $item->id;
                    }
                } else {
                    $item = MasterCategory::create($data);
                    $existingIds[] = $item->id;
                }
            }

            // Delete items not in the submitted list for this type
            MasterCategory::where('type', $dbType)
                ->whereNotIn('id', $existingIds)
                ->delete();
        }

        // Return refreshed grouped data
        $grouped = [
            'kategori' => MasterCategory::ofType('kategori')->orderBy('sort_order')->get(),
            'tipeBerita' => MasterCategory::ofType('tipe_berita')->orderBy('sort_order')->get(),
            'label' => MasterCategory::ofType('label')->orderBy('sort_order')->get(),
            'status' => MasterCategory::ofType('status')->orderBy('sort_order')->get(),
        ];

        return $this->successResponse($grouped, 'Master data updated successfully.');
    }
}
