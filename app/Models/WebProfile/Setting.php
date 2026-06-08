<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'nama_masjid',
        'slogan',
        'deskripsi_sambutan',
        'sejarah_singkat',
        'link_instagram',
        'no_whatsapp',
        'link_maps',
    ];
}
