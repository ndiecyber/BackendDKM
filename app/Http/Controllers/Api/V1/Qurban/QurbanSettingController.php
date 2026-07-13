<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Models\Qurban\QurbanSetting;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Qurban - Pengaturan')]
class QurbanSettingController extends Controller
{
    use ApiResponse;

    /**
     * Get all qurban settings (admin).
     */
    public function index(): JsonResponse
    {
        Gate::authorize('qurban.settings.view');

        $settings = QurbanSetting::pluck('value', 'key');

        return $this->successResponse($settings);
    }

    /**
     * Update qurban settings (admin).
     */
    public function update(Request $request): JsonResponse
    {
        Gate::authorize('qurban.settings.update');

        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        foreach ($data['settings'] as $key => $value) {
            QurbanSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $settings = QurbanSetting::pluck('value', 'key');

        return $this->successResponse($settings, 'Pengaturan berhasil disimpan.');
    }
}
