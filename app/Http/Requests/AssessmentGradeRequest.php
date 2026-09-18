<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class AssessmentGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        $max = $this->route('assessment') instanceof Assessment
            ? $this->route('assessment')->max_score
            : 1000;

        return [
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'between:0,'.$max],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'scores.required' => 'Nilai belum diisi.',
            'scores.*.numeric' => 'Nilai harus berupa angka.',
            'scores.*.between' => 'Nilai harus antara 0 dan nilai maksimal.',
        ];
    }
}
