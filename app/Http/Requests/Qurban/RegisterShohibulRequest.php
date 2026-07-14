<?php

namespace App\Http\Requests\Qurban;

use App\Models\Qurban\QurbanSetting;
use Illuminate\Foundation\Http\FormRequest;

class RegisterShohibulRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        $paymentMode = QurbanSetting::where('key', 'payment_mode')->value('value') ?? 'gateway';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'target_type' => ['required', 'in:sapi,kambing'],
            'initial_amount' => ['required', 'integer', 'min:50000', 'multiple_of:50000'],
        ];

        if ($paymentMode === 'manual') {
            $rules['payment_method'] = ['required', 'string', 'in:qris,transfer_bsi'];
            $rules['payment_proof'] = ['required', 'image', 'max:5120'];
        } else {
            $rules['payment_method'] = ['required', 'string', 'in:tunai,transfer,qris,bri_va,bni_va,cimb_niaga_va,permata_va,maybank_va,sampoerna_va,bnc_va,artha_graha_va,atm_bersama_va'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'initial_amount.min' => 'Setoran awal minimal Rp 50.000.',
            'initial_amount.multiple_of' => 'Setoran harus kelipatan Rp 50.000.',
            'target_type.in' => 'Jenis hewan harus sapi atau kambing.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'payment_proof.required' => 'Bukti pembayaran wajib diupload.',
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar (JPG, PNG).',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5MB.',
        ];
    }
}
