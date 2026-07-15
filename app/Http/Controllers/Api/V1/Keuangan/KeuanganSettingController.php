<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\KeuanganSetting;
use App\Services\ImageUploadService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

#[Group('Keuangan - Pengaturan')]
class KeuanganSettingController extends Controller
{
    use ApiResponse;

    /**
     * Get all keuangan settings (admin).
     */
    public function index(): JsonResponse
    {
        Gate::authorize('keuangan.view');

        $settings = KeuanganSetting::pluck('value', 'key');

        return $this->successResponse($settings);
    }

    /**
     * Update keuangan settings (admin).
     */
    public function update(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.update');

        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
            'qris_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $settings = $request->input('settings', []);

        if ($request->hasFile('qris_image')) {
            // Retrieve old image path
            $oldImageSetting = KeuanganSetting::where('key', 'donation_qris_image_path')->first();

            if ($oldImageSetting && $oldImageSetting->value) {
                Storage::disk('public')->delete($oldImageSetting->value);
            }

            $settings['donation_qris_image_path'] = ImageUploadService::storeAsWebp($request->file('qris_image'), 'keuangan/settings', 'public');
        }

        foreach ($settings as $key => $value) {
            KeuanganSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $updatedSettings = KeuanganSetting::pluck('value', 'key');

        return $this->successResponse($updatedSettings, 'Pengaturan berhasil disimpan.');
    }

    /**
     * Get public keuangan settings (landing page).
     */
    public function publicSettings(): JsonResponse
    {
        // Only return specific keys that are safe for public access
        $keys = [
            'donation_payment_bank',
            'donation_payment_account',
            'donation_payment_name',
            'donation_qris_image_path',
            'landing_program_mode',
            'landing_program_limit',
        ];

        $settings = KeuanganSetting::whereIn('key', $keys)->pluck('value', 'key');

        return $this->successResponse($settings);
    }
}
