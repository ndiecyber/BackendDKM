<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_masjid',
        'slogan',
        'deskripsi_sambutan',
        'sejarah_singkat',
        'link_instagram',
        'link_facebook',
        'link_youtube',
        'link_twitter',
        'link_tiktok',
        'no_whatsapp',
        'email',
        'telepon_kantor',
        'link_maps',
        'maps_iframe',
        'alamat_lengkap',
        'kota',
        'kodepos',
        'floating_card_title',
        'floating_card_desc',
        'tahun_berdiri',
        'jamaah_aktif',
        'hero_images',
        'history_image',
        'committee_description',
    ];

    protected function casts(): array
    {
        return [
            'hero_images' => 'array',
            'tahun_berdiri' => 'integer',
            'jamaah_aktif' => 'integer',
        ];
    }
}
