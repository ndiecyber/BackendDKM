<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBankKasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('keuangan.bank_kas.update');
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
            'tipe' => ['sometimes', 'in:tunai,rekening,dompet_digital'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
            'atas_nama' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['sometimes', 'in:aktif,non_aktif'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_pinned' => ['sometimes', 'boolean'],
            'visibilitas_publik' => ['sometimes', 'boolean'],
        ];
    }
}
