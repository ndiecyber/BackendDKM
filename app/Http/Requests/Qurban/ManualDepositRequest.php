<?php

namespace App\Http\Requests\Qurban;

use Illuminate\Foundation\Http\FormRequest;

class ManualDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth checked via Gate in controller
    }

    public function rules(): array
    {
        return [
            'shohibul_id' => ['required', 'integer', 'exists:shohibuls,id'],
            'amount' => ['required', 'integer', 'min:50000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Setoran tunai minimal Rp 50.000.',
            'shohibul_id.exists' => 'Data shohibul tidak ditemukan.',
        ];
    }
}
