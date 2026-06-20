<?php

namespace Database\Factories\WebProfile;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_masjid' => fake()->company(),
            'slogan' => fake()->sentence(),
            'deskripsi_sambutan' => fake()->paragraph(),
            'sejarah_singkat' => fake()->paragraph(),
            'no_whatsapp' => fake()->phoneNumber(),
            'link_maps' => fake()->url(),
            'floating_card_title' => fake()->words(3, true),
            'floating_card_desc' => fake()->sentence(),
            'tahun_berdiri' => fake()->year(),
            'jamaah_aktif' => fake()->numberBetween(100, 1000),
            'hero_images' => ['/storage/hero/1.jpg', '/storage/hero/2.jpg'],
            'history_image' => '/storage/history.jpg',
            'committee_description' => fake()->paragraph(),
            'link_instagram' => fake()->url(),
            'link_facebook' => fake()->url(),
            'link_youtube' => fake()->url(),
            'link_twitter' => fake()->url(),
            'link_tiktok' => fake()->url(),
            'email' => fake()->safeEmail(),
            'telepon_kantor' => fake()->phoneNumber(),
            'alamat_lengkap' => fake()->address(),
            'kota' => fake()->city(),
            'kodepos' => fake()->postcode(),
            'maps_iframe' => '<iframe src="..."></iframe>',
        ];
    }
}
