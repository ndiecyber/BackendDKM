<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'tipe' => ['required', 'in:pemasukan,pengeluaran,transfer'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'tanggal' => ['nullable', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'jamaah_id' => ['nullable', 'exists:jamaah,id'],
            'status' => ['sometimes', 'in:draft,pending,approved'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];

        // Dynamic validation based on tipe
        if ($this->tipe === 'pemasukan') {
            $rules['bank_kas_tujuan_id'] = ['required', 'exists:bank_kas,id'];
            $rules['bank_kas_asal_id'] = ['nullable'];
        } elseif ($this->tipe === 'pengeluaran') {
            $rules['bank_kas_asal_id'] = ['required', 'exists:bank_kas,id'];
            $rules['bank_kas_tujuan_id'] = ['nullable'];
        } elseif ($this->tipe === 'transfer') {
            $rules['bank_kas_asal_id'] = ['required', 'exists:bank_kas,id'];
            $rules['bank_kas_tujuan_id'] = ['required', 'exists:bank_kas,id', 'different:bank_kas_asal_id'];
            $rules['biaya_admin'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'bank_kas_tujuan_id.different' => 'Bank/Kas tujuan harus berbeda dengan Bank/Kas asal.',
            'nominal.min' => 'Nominal transaksi minimal Rp 1.',
        ];
    }
}
