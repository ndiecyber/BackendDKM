<?php

namespace App\Models\Qurban;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QurbanSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];
}
