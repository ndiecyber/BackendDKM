<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\CtaProgram;
use App\Models\WebProfile\CtaSetting;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profil Web - Donasi & CTA')]
class CtaSettingController extends Controller
{
    use ApiResponse;

    /**
     * Get CTA (Call-to-Action) donation settings.
     *
     * Returns the donation section settings with associated programs/progress bars.
     */
    public function show(): JsonResponse
    {
        $cta = CtaSetting::with('programs')->firstOrCreate(['id' => 1], [
            'title' => 'Investasi Terbaik Untuk Akhirat',
            'subtitle' => 'Setiap rupiah yang Anda sedekahkan tidak hanya memakmurkan masjid, tapi juga mengalirkan pahala yang tak terputus.',
            'quote' => '"Barang siapa yang membangun masjid karena Allah, maka Allah akan membangunkan baginya rumah di surga."',
            'quote_source' => 'HR. Bukhari & Muslim',
            'total_donors' => 0,
            'slider_images' => [],
        ]);

        return $this->successResponse($cta);
    }

    /**
     * Update CTA donation settings.
     *
     * Updates the CTA settings and syncs the associated programs (progress bars).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string',
            'quote' => 'nullable|string',
            'quote_source' => 'nullable|string|max:255',
            'total_donors' => 'nullable|integer|min:0',
            'slider_images' => 'nullable|array',
            'slider_images.*' => 'string',

            'programs' => 'nullable|array',
            'programs.*.id' => 'nullable|integer',
            'programs.*.name' => 'required_with:programs|string|max:255',
            'programs.*.progress' => 'required_with:programs|integer|min:0|max:100',
        ]);

        $cta = CtaSetting::firstOrCreate(['id' => 1]);

        // Separate programs from settings
        $programsData = $validated['programs'] ?? null;
        unset($validated['programs']);

        $cta->update($validated);

        // Sync programs if provided
        if ($programsData !== null) {
            $existingIds = [];

            foreach ($programsData as $index => $programData) {
                if (!empty($programData['id'])) {
                    $program = CtaProgram::where('cta_setting_id', $cta->id)
                        ->find($programData['id']);
                    if ($program) {
                        $program->update([
                            'name' => $programData['name'],
                            'progress' => $programData['progress'],
                            'sort_order' => $index,
                        ]);
                        $existingIds[] = $program->id;
                    }
                } else {
                    $program = CtaProgram::create([
                        'cta_setting_id' => $cta->id,
                        'name' => $programData['name'],
                        'progress' => $programData['progress'],
                        'sort_order' => $index,
                    ]);
                    $existingIds[] = $program->id;
                }
            }

            // Delete programs not in the submitted list
            CtaProgram::where('cta_setting_id', $cta->id)
                ->whereNotIn('id', $existingIds)
                ->delete();
        }

        $cta->load('programs');

        return $this->successResponse($cta, 'CTA settings updated successfully.');
    }
}
