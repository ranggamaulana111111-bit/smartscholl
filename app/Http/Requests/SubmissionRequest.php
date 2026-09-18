<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('siswa');
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt,image/jpeg,image/png'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.max' => 'Catatan maksimal 1000 karakter.',
            'attachment.max' => 'Berkas maksimal 5 MB.',
        ];
    }
}
