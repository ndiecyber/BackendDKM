<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\WebProfile\Setting;
use App\Models\WebProfile\WhatsappContact;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profil Web - Pengaturan')]
class SettingController extends Controller
{
    use ApiResponse;

    /**
     * Get website settings.
     *
     * Returns the complete website settings including WhatsApp contacts.
     */
    public function show(): JsonResponse
    {
        $setting = Setting::firstOrCreate(['id' => 1]);
        $whatsappContacts = WhatsappContact::orderBy('sort_order')->get();

        $data = $setting->toArray();
        $data['whatsapp'] = $whatsappContacts;

        return $this->successResponse($data);
    }

    /**
     * Update website settings.
     *
     * Updates all website settings including WhatsApp contacts.
     * WhatsApp contacts are synced: existing ones are updated, new ones created, missing ones deleted.
     */
    public function update(Request $request): JsonResponse
    {
        $setting = Setting::firstOrCreate(['id' => 1]);

        $validated = $request->validate([
            // Profil & Sejarah
            'nama_masjid' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:500',
            'deskripsi_sambutan' => 'nullable|string',
            'sejarah_singkat' => 'nullable|string',
            'floating_card_title' => 'nullable|string|max:255',
            'floating_card_desc' => 'nullable|string|max:500',
            'tahun_berdiri' => 'nullable|integer|min:1900|max:2100',
            'jamaah_aktif' => 'nullable|integer|min:0',
            'hero_images' => 'nullable|array',
            'hero_images.*' => 'string',
            'history_image' => 'nullable|string',
            'committee_description' => 'nullable|string',

            // Sosial Media
            'link_instagram' => 'nullable|string|max:255',
            'link_facebook' => 'nullable|string|max:255',
            'link_youtube' => 'nullable|string|max:255',
            'link_twitter' => 'nullable|string|max:255',
            'link_tiktok' => 'nullable|string|max:255',

            // Kontak
            'no_whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|string|email|max:255',
            'telepon_kantor' => 'nullable|string|max:30',

            // Lokasi
            'alamat_lengkap' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'kodepos' => 'nullable|string|max:10',
            'link_maps' => 'nullable|string|max:500',
            'maps_iframe' => 'nullable|string',

            // WhatsApp contacts (nested array)
            'whatsapp' => 'nullable|array',
            'whatsapp.*.id' => 'nullable|integer',
            'whatsapp.*.name' => 'required_with:whatsapp|string|max:255',
            'whatsapp.*.number' => 'required_with:whatsapp|string|max:20',
        ]);

        // Separate whatsapp contacts from settings data
        $whatsappData = $validated['whatsapp'] ?? null;
        unset($validated['whatsapp']);

        $setting->update($validated);

        // Sync WhatsApp contacts if provided
        if ($whatsappData !== null) {
            $existingIds = [];

            foreach ($whatsappData as $index => $contactData) {
                if (!empty($contactData['id'])) {
                    // Update existing
                    $contact = WhatsappContact::find($contactData['id']);
                    if ($contact) {
                        $contact->update([
                            'name' => $contactData['name'],
                            'number' => $contactData['number'],
                            'sort_order' => $index,
                        ]);
                        $existingIds[] = $contact->id;
                    }
                } else {
                    // Create new
                    $contact = WhatsappContact::create([
                        'name' => $contactData['name'],
                        'number' => $contactData['number'],
                        'sort_order' => $index,
                    ]);
                    $existingIds[] = $contact->id;
                }
            }

            // Delete contacts not in the submitted list
            WhatsappContact::whereNotIn('id', $existingIds)->delete();
        }

        // Reload and return
        $setting->refresh();
        $data = $setting->toArray();
        $data['whatsapp'] = WhatsappContact::orderBy('sort_order')->get();

        return $this->successResponse($data, 'Settings updated successfully.');
    }
}
