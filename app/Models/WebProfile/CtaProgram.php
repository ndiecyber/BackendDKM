<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtaProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'cta_setting_id',
        'name',
        'progress',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function ctaSetting(): BelongsTo
    {
        return $this->belongsTo(CtaSetting::class);
    }
}
