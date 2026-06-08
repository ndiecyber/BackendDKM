<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    /**
     * Get website settings.
     */
    public function show(): JsonResponse
    {
        // Get the first setting row or create a default empty one
        $setting = Setting::firstOrCreate(['id' => 1]);

        return $this->successResponse($setting);
    }

    /**
     * Update website settings.
     */
    public function update(Request $request): JsonResponse
    {
        $setting = Setting::firstOrCreate(['id' => 1]);

        $validated = $request->validate([
            'nama_masjid' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'deskripsi_sambutan' => 'nullable|string',
            'sejarah_singkat' => 'nullable|string',
            'link_instagram' => 'nullable|string|max:255',
            'no_whatsapp' => 'nullable|string|max:20',
            'link_maps' => 'nullable|string|max:255',
        ]);

        $setting->update($validated);

        return $this->successResponse($setting, 'Settings updated successfully.');
    }
}
