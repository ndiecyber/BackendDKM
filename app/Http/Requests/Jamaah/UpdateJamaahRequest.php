<?php

namespace App\Http\Requests\Jamaah;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJamaahRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('jamaah.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => ['sometimes', 'string', 'max:255'],
            'kategori_entitas' => ['sometimes', 'in:individu,organisasi'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'tipe_jamaah' => ['sometimes', 'in:internal_dkm,warga_sekitar,eksternal,mitra_organisasi'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
