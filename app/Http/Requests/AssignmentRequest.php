<?php

namespace App\Http\Requests;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        return [
            'rombel_id' => ['required', 'exists:rombels,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (! auth()->user()->isGuru()) {
            return;
        }

        $validator->after(function ($validator) {
            $taught = Schedule::where('user_id', auth()->id())
                ->where('subject_id', $this->input('subject_id'))
                ->where('rombel_id', $this->input('rombel_id'))
                ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                ->exists();

            if (! $taught) {
                $validator->errors()->add('subject_id', 'Anda tidak dijadwalkan mengajar mata pelajaran ini di rombel tersebut.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'rombel_id.required' => 'Rombel wajib dipilih.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'title.required' => 'Judul tugas wajib diisi.',
            'deadline_at.required' => 'Batas waktu pengumpulan wajib diisi.',
            'deadline_at.after' => 'Batas waktu harus berupa waktu yang akan datang.',
            'attachment.mimes' => 'Berkas acuan harus PDF, dokumen Office, atau ZIP.',
        ];
    }
}
