<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceAdjustment extends Model
{
    protected $fillable = [
        'bank_kas_id',
        'saldo_sebelum',
        'saldo_sesudah',
        'selisih',
        'tanggal',
        'deskripsi',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'saldo_sebelum' => 'decimal:2',
            'saldo_sesudah' => 'decimal:2',
            'selisih' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function bankKas()
    {
        return $this->belongsTo(BankKas::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
