<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Gallery;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

#[Group('Profil Web - Galeri')]
class GalleryController extends Controller
{
    use ApiResponse;

    /**
     * List all gallery images.
     *
     * Supports optional filtering by tag, category, and active status.
     */
    public function index(Request $request): JsonResponse
    {
        $galleries = Gallery::query()
            ->when($request->tag, fn ($q, $tag) => $q->where('tag', $tag))
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return $this->successResponse($galleries);
    }

    /**
     * Upload a new gallery image.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'caption' => 'nullable|string',
            'subcaption' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'icon_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $path = $request->file('image')->store('galleries', 'public');

        $gallery = Gallery::create([
            'image_path' => Storage::url($path),
            'caption' => $request->caption,
            'subcaption' => $request->subcaption,
            'tag' => $request->tag,
            'category' => $request->category,
            'icon_name' => $request->icon_name,
            'is_active' => $request->is_active ?? true,
            'sort_order' => Gallery::max('sort_order') + 1,
        ]);

        return $this->createdResponse($gallery, 'Gallery image uploaded successfully.');
    }

    /**
     * Show a single gallery image.
     */
    public function show(string $id): JsonResponse
    {
        $gallery = Gallery::findOrFail($id);

        return $this->successResponse($gallery);
    }

    /**
     * Update a gallery image's metadata (and optionally replace the image).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $gallery = Gallery::findOrFail($id);

        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'caption' => 'nullable|string',
            'subcaption' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'icon_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Handle image replacement
        if ($request->hasFile('image')) {
            // Delete old image
            $oldPath = str_replace('/storage/', '', $gallery->image_path);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('galleries', 'public');
            $validated['image_path'] = Storage::url($path);
        }
        unset($validated['image']);

        $gallery->update($validated);

        return $this->successResponse($gallery, 'Gallery updated successfully.');
    }

    /**
     * Delete a gallery image.
     */
    public function destroy(string $id): JsonResponse
    {
        $gallery = Gallery::findOrFail($id);

        // Extract path from storage url
        $path = str_replace('/storage/', '', $gallery->image_path);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $gallery->delete();

        return $this->successResponse(null, 'Gallery deleted successfully.');
    }
}
