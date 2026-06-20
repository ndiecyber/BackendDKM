<?php

namespace App\Http\Requests\Qurban;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth checked via Gate in controller
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'target_type' => ['required', 'in:sapi,kambing'],
        ];
    }
}
