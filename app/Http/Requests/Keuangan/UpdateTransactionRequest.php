<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('keuangan.transaksi.update');
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
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'nominal' => ['sometimes', 'numeric', 'min:1'],
            'tanggal' => ['sometimes', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'bank_kas_asal_id' => ['nullable', 'exists:bank_kas,id'],
            'bank_kas_tujuan_id' => ['nullable', 'exists:bank_kas,id'],
            'jamaah_id' => ['nullable', 'exists:jamaah,id'],
            'biaya_admin' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }
}
