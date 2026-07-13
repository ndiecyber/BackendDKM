<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\CommitteeDivision;
use App\Models\WebProfile\CommitteeMember;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageUploadService;
use Illuminate\Support\Str;

#[Group('Profil Web - Pengurus DKM')]
class CommitteeController extends Controller
{
    use ApiResponse;

    /**
     * Get full committee structure.
     *
     * Returns the complete DKM committee structure:
     * dewan penasihat, pengurus harian, and divisions with their members.
     */
    public function index(): JsonResponse
    {
        $dewanPenasihat = CommitteeMember::group('dewan_penasihat')
            ->orderBy('sort_order')
            ->get();

        $pengurusHarian = CommitteeMember::group('pengurus_harian')
            ->orderBy('sort_order')
            ->get();

        $divisi = CommitteeDivision::with(['members' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse([
            'dewanPenasihat' => $dewanPenasihat,
            'pengurusHarian' => $pengurusHarian,
            'divisi' => $divisi,
        ]);
    }

    /**
     * Bulk update the entire committee structure.
     *
     * Accepts the full committee structure and syncs:
     * - dewanPenasihat members
     * - pengurusHarian members
     * - divisi (divisions) with their nested members
     *
     * Images can be uploaded via multipart/form-data.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dewanPenasihat' => 'nullable|array',
            'dewanPenasihat.*.id' => 'nullable|integer',
            'dewanPenasihat.*.name' => 'required|string|max:255',
            'dewanPenasihat.*.role' => 'nullable|string|max:255',
            'dewanPenasihat.*.image' => 'nullable|string',
            'dewanPenasihat.*.isLeader' => 'boolean',

            'pengurusHarian' => 'nullable|array',
            'pengurusHarian.*.id' => 'nullable|integer',
            'pengurusHarian.*.name' => 'required|string|max:255',
            'pengurusHarian.*.role' => 'nullable|string|max:255',
            'pengurusHarian.*.image' => 'nullable|string',
            'pengurusHarian.*.isLeader' => 'boolean',

            'divisi' => 'nullable|array',
            'divisi.*.id' => 'nullable|integer',
            'divisi.*.name' => 'required|string|max:255',
            'divisi.*.members' => 'nullable|array',
            'divisi.*.members.*.id' => 'nullable|integer',
            'divisi.*.members.*.name' => 'required|string|max:255',
            'divisi.*.members.*.role' => 'nullable|string|max:255',
            'divisi.*.members.*.image' => 'nullable|string',
            'divisi.*.members.*.isLeader' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Sync Dewan Penasihat
            $this->syncMembers('dewan_penasihat', $validated['dewanPenasihat'] ?? [], $request);

            // Sync Pengurus Harian
            $this->syncMembers('pengurus_harian', $validated['pengurusHarian'] ?? [], $request);

            // Sync Divisi & their members
            $this->syncDivisions($validated['divisi'] ?? [], $request);
        });

        // Return fresh data
        return $this->index();
    }

    /**
     * Upload a committee member photo.
     *
     * Accepts image upload and returns the stored path.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $path = ImageUploadService::storeAsWebp($request->file('image'), 'committee', 'public');

        return $this->successResponse([
            'image_path' => Storage::url($path),
        ], 'Photo uploaded successfully.');
    }

    /**
     * Sync members for a given group (dewan_penasihat or pengurus_harian).
     */
    private function syncMembers(string $group, array $membersData, Request $request): void
    {
        $existingIds = [];

        foreach ($membersData as $index => $memberData) {
            $data = [
                'group' => $group,
                'division_id' => null,
                'name' => $memberData['name'],
                'role' => $memberData['role'] ?? null,
                'is_leader' => $memberData['isLeader'] ?? false,
                'sort_order' => $index,
            ];

            // Handle image: if it's a path string (already uploaded), keep it
            if (isset($memberData['image']) && $memberData['image']) {
                $data['image'] = $memberData['image'];
            }

            if (! empty($memberData['id'])) {
                $member = CommitteeMember::find($memberData['id']);
                if ($member && $member->group === $group) {
                    // Only update image if explicitly provided
                    if (! isset($memberData['image'])) {
                        unset($data['image']);
                    }
                    $member->update($data);
                    $existingIds[] = $member->id;
                } else {
                    $member = CommitteeMember::create($data);
                    $existingIds[] = $member->id;
                }
            } else {
                $member = CommitteeMember::create($data);
                $existingIds[] = $member->id;
            }
        }

        // Delete members not in the submitted list
        $toDelete = CommitteeMember::where('group', $group)
            ->whereNull('division_id')
            ->whereNotIn('id', $existingIds)
            ->get();

        foreach ($toDelete as $member) {
            $this->deleteImage($member->image);
            $member->delete();
        }
    }

    /**
     * Sync divisions and their members.
     */
    private function syncDivisions(array $divisiData, Request $request): void
    {
        $existingDivisionIds = [];

        foreach ($divisiData as $divIndex => $divData) {
            $slug = Str::slug($divData['name']);

            if (! empty($divData['id'])) {
                $division = CommitteeDivision::find($divData['id']);
                if ($division) {
                    $division->update([
                        'name' => $divData['name'],
                        'slug' => $slug,
                        'sort_order' => $divIndex,
                    ]);
                } else {
                    $division = CommitteeDivision::create([
                        'name' => $divData['name'],
                        'slug' => $slug.'-'.Str::random(4),
                        'sort_order' => $divIndex,
                    ]);
                }
            } else {
                // Ensure unique slug
                $existingSlug = CommitteeDivision::where('slug', $slug)->exists();
                $division = CommitteeDivision::create([
                    'name' => $divData['name'],
                    'slug' => $existingSlug ? $slug.'-'.Str::random(4) : $slug,
                    'sort_order' => $divIndex,
                ]);
            }

            $existingDivisionIds[] = $division->id;

            // Sync division members
            $memberIds = [];
            foreach (($divData['members'] ?? []) as $memIndex => $memData) {
                $data = [
                    'group' => 'divisi',
                    'division_id' => $division->id,
                    'name' => $memData['name'],
                    'role' => $memData['role'] ?? null,
                    'is_leader' => $memData['isLeader'] ?? false,
                    'sort_order' => $memIndex,
                ];

                if (isset($memData['image']) && $memData['image']) {
                    $data['image'] = $memData['image'];
                }

                if (! empty($memData['id'])) {
                    $member = CommitteeMember::find($memData['id']);
                    if ($member && $member->division_id === $division->id) {
                        if (! isset($memData['image'])) {
                            unset($data['image']);
                        }
                        $member->update($data);
                        $memberIds[] = $member->id;
                    } else {
                        $member = CommitteeMember::create($data);
                        $memberIds[] = $member->id;
                    }
                } else {
                    $member = CommitteeMember::create($data);
                    $memberIds[] = $member->id;
                }
            }

            // Delete division members not in list
            $toDelete = CommitteeMember::where('division_id', $division->id)
                ->whereNotIn('id', $memberIds)
                ->get();

            foreach ($toDelete as $member) {
                $this->deleteImage($member->image);
                $member->delete();
            }
        }

        // Delete divisions not in list (cascade deletes members via FK)
        $divisionsToDelete = CommitteeDivision::whereNotIn('id', $existingDivisionIds)->get();
        foreach ($divisionsToDelete as $div) {
            // Delete images of members in this division
            foreach ($div->members as $member) {
                $this->deleteImage($member->image);
            }
            $div->delete();
        }
    }

    /**
     * Delete an image file from storage.
     */
    private function deleteImage(?string $imagePath): void
    {
        if (! $imagePath) {
            return;
        }

        $path = str_replace('/storage/', '', $imagePath);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
