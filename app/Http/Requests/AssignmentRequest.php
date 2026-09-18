<?php

namespace App\Http\Requests;

use App\Models\Rombel;
use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        $tenantId = currentTenantId();

        return [
            'rombel_id' => [
                'required',
                'integer',
                Rule::exists('rombels', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $rombelId = $this->input('rombel_id');

            if ($rombelId) {
                $rombel = Rombel::find($rombelId);

                if ($rombel && ! $rombel->academicYear?->is_active) {
                    $validator->errors()->add('rombel_id', 'Rombel harus berasal dari tahun ajaran aktif.');
                }
            }

            if (! auth()->user()->isGuru()) {
                return;
            }

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
            'rombel_id.exists' => 'Rombel tidak ditemukan pada sekolah ini.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran aktif tidak ditemukan pada sekolah ini.',
            'title.required' => 'Judul tugas wajib diisi.',
            'deadline_at.required' => 'Batas waktu pengumpulan wajib diisi.',
            'deadline_at.after' => 'Batas waktu harus berupa waktu yang akan datang.',
            'attachment.mimes' => 'Berkas acuan harus PDF, dokumen Office, atau ZIP.',
        ];
    }
}
