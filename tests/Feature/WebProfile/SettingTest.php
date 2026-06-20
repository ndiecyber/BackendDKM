<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\Setting;
use App\Models\WebProfile\WhatsappContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_settings_publicly(): void
    {
        Setting::factory()->create(['nama_masjid' => 'Masjid Raya Test']);
        WhatsappContact::factory()->count(2)->create();

        $response = $this->getJson('/v1/web-profile/settings');

        $response->assertStatus(200)
            ->assertJsonPath('data.nama_masjid', 'Masjid Raya Test')
            ->assertJsonCount(2, 'data.whatsapp');
    }

    public function test_admin_can_update_settings(): void
    {
        $user = User::factory()->create();
        $setting = Setting::factory()->create();

        $response = $this->actingAs($user)->putJson('/v1/web-profile/settings', [
            'nama_masjid' => 'Updated Mosque Name',
            'whatsapp' => [
                ['name' => 'Admin 1', 'number' => '081234567890'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'nama_masjid' => 'Updated Mosque Name',
        ]);
        $this->assertDatabaseHas('whatsapp_contacts', [
            'name' => 'Admin 1',
            'number' => '081234567890',
        ]);
    }
}
