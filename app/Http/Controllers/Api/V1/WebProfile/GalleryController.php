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

    public function index(): JsonResponse
    {
        $galleries = Gallery::latest()->get();

        return $this->successResponse($galleries);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240', // max 10MB
            'caption' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $path = $request->file('image')->store('galleries', 'public');

        $gallery = Gallery::create([
            'image_path' => Storage::url($path),
            'caption' => $request->caption,
            'is_active' => $request->is_active ?? true,
        ]);

        return $this->createdResponse($gallery, 'Gallery image uploaded successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $gallery = Gallery::findOrFail($id);

        return $this->successResponse($gallery);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $gallery = Gallery::findOrFail($id);

        $validated = $request->validate([
            'caption' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $gallery->update($validated);

        return $this->successResponse($gallery, 'Gallery updated successfully.');
    }

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
