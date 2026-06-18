<?php

namespace App\Http\Requests\Qurban;

use Illuminate\Foundation\Http\FormRequest;

class MoveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth checked via Gate in controller
    }

    public function rules(): array
    {
        return [
            'shohibul_id' => ['required', 'integer', 'exists:shohibuls,id'],
            'new_group_id' => ['required', 'integer', 'exists:animal_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'shohibul_id.exists' => 'Data shohibul tidak ditemukan.',
            'new_group_id.exists' => 'Kelompok tujuan tidak ditemukan.',
        ];
    }
}
