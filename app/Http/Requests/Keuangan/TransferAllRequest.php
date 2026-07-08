<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Foundation\Http\FormRequest;

class TransferAllRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('keuangan.transaksi.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'bank_kas_tujuan_id' => ['required', 'exists:bank_kas,id', 'different:id'],
            'biaya_admin' => ['nullable', 'numeric', 'min:0'],
            'tanggal' => ['nullable', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_kas_tujuan_id.required' => 'Rekening tujuan wajib dipilih',
            'bank_kas_tujuan_id.different' => 'Rekening tujuan tidak boleh sama dengan rekening asal',
            'biaya_admin.numeric' => 'Biaya admin harus berupa angka',
            'biaya_admin.min' => 'Biaya admin tidak boleh kurang dari 0',
        ];
    }
}
