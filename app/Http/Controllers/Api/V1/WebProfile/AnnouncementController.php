<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Announcement;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profil Web - Pengumuman')]
class AnnouncementController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $announcements = Announcement::latest()->get();

        return $this->successResponse($announcements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $announcement = Announcement::create($validated);

        return $this->createdResponse($announcement, 'Announcement created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        return $this->successResponse($announcement);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $announcement->update($validated);

        return $this->successResponse($announcement, 'Announcement updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return $this->successResponse(null, 'Announcement deleted successfully.');
    }
}
