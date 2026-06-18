<?php

namespace App\Http\Requests\Qurban;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShohibulRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth checked via Gate in controller
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:500'],
            'target_type' => ['sometimes', 'in:sapi,kambing'],
        ];
    }
}
