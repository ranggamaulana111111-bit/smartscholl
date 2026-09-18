<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'rombel_id' => ['nullable', 'exists:rombels,id'],
            'category' => ['required', Rule::in(['tugas', 'formatif', 'uts', 'uas'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'between:1,1000'],
            'weight_percentage' => ['nullable', 'integer', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'category.required' => 'Kategori penilaian wajib dipilih.',
            'title.required' => 'Judul penilaian wajib diisi.',
            'max_score.required' => 'Nilai maksimal wajib diisi.',
        ];
    }
}
