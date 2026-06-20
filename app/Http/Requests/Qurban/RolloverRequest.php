<?php

namespace App\Http\Requests\Qurban;

use Illuminate\Foundation\Http\FormRequest;

class RolloverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth checked via Gate in controller
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sapi_price_per_slot' => ['required', 'numeric', 'min:0'],
            'kambing_price' => ['required', 'numeric', 'min:0'],
            'deadline_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'deadline_date.after' => 'Deadline harus setelah hari ini.',
        ];
    }
}
