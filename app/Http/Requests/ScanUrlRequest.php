<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url:http,https', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'URL wajib diisi.',
            'url.url' => 'Format URL tidak valid (harus diawali http:// atau https://).',
            'url.max' => 'URL terlalu panjang (maksimal 2048 karakter).',
        ];
    }
}
