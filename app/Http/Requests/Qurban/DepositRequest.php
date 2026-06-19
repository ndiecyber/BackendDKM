<?php

namespace App\Http\Requests\Qurban;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'shohibul_id' => ['required', 'integer', 'exists:shohibuls,id'],
            'amount' => ['required', 'integer', 'min:50000', 'multiple_of:50000'],
            'payment_method' => ['required', 'string', 'in:qris,bri_va,bni_va,cimb_niaga_va,permata_va,maybank_va,sampoerna_va,bnc_va,artha_graha_va,atm_bersama_va'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Setoran minimal Rp 50.000.',
            'amount.multiple_of' => 'Setoran harus kelipatan Rp 50.000.',
            'shohibul_id.exists' => 'Data shohibul tidak ditemukan.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ];
    }
}
