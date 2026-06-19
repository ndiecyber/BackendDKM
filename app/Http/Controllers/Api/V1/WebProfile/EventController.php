<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Event;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profil Web - Kegiatan')]
class EventController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $events = Event::orderBy('date', 'desc')->get();

        return $this->successResponse($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $event = Event::create($validated);

        return $this->createdResponse($event, 'Event created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        return $this->successResponse($event);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $event->update($validated);

        return $this->successResponse($event, 'Event updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return $this->successResponse(null, 'Event deleted successfully.');
    }
}
