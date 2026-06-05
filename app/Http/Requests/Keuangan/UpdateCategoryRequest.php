<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('keuangan.category.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['sometimes', 'string', 'max:255'],
            'tipe' => ['sometimes', 'in:pemasukan,pengeluaran'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:aktif,non_aktif'],
            'visibilitas' => ['sometimes', 'in:publik,internal'],
        ];
    }
}
