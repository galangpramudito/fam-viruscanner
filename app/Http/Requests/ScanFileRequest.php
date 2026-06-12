<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimetypes:application/vnd.android.package-archive,application/x-msdownload,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/x-zip-compressed,application/octet-stream',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File wajib diunggah.',
            'file.file' => 'Unggahan tidak valid.',
            'file.max' => 'Ukuran file terlalu besar (maksimal 20 MB).',
            'file.mimetypes' => 'Tipe file tidak didukung. Gunakan APK, EXE, PDF, DOC, DOCX, atau ZIP.',
        ];
    }
}
