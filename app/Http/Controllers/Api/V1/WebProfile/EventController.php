<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Event;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

#[Group('Profil Web - Kegiatan')]
class EventController extends Controller
{
    use ApiResponse;

    /**
     * List all events/articles.
     *
     * Returns all kegiatan/berita ordered by date, newest first.
     * Supports optional filtering by type, category, and active status.
     */
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('date', 'desc')
            ->get();

        return $this->successResponse($events);
    }

    /**
     * Create a new event/article.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'location' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('events', 'public');
            $validated['image'] = Storage::url($path);
        }

        $event = Event::create($validated);

        return $this->createdResponse($event, 'Event created successfully.');
    }

    /**
     * Show a single event/article.
     *
     * Also increments the hit counter for the event.
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        // Increment hits
        $event->increment('hits');

        return $this->successResponse($event);
    }

    /**
     * Update an existing event/article.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'location' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($event->image) {
                $oldPath = str_replace('/storage/', '', $event->image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('image')->store('events', 'public');
            $validated['image'] = Storage::url($path);
        }

        $event->update($validated);

        return $this->successResponse($event, 'Event updated successfully.');
    }

    /**
     * Delete an event/article.
     */
    public function destroy(string $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        // Delete image file if present
        if ($event->image) {
            $path = str_replace('/storage/', '', $event->image);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $event->delete();

        return $this->successResponse(null, 'Event deleted successfully.');
    }

    /**
     * Upload an image for rich text editor.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif',
        ]);

        $path = $request->file('image')->store('events/content', 'public');
        $url = Storage::url($path);

        // We return an absolute URL by combining app url and storage url
        $fullUrl = url($url);

        return $this->successResponse(['url' => $fullUrl], 'Image uploaded successfully.');
    }
}
