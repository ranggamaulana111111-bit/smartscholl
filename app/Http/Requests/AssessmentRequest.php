<?php

namespace App\Http\Requests;

use App\Models\Rombel;
use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        $tenantId = currentTenantId();

        $rules = [
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'rombel_id' => [
                'nullable',
                'integer',
                Rule::exists('rombels', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'category' => ['required', Rule::in(['tugas', 'formatif', 'uts', 'uas'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'between:1,1000'],
            'weight_percentage' => ['nullable', 'integer', 'between:0,100'],
        ];

        if (auth()->user()->isGuru()) {
            $rules['rombel_id'][] = 'required';
        }

        return $rules;
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
                $validator->errors()->add('rombel_id', 'Anda tidak dijadwalkan mengajar mata pelajaran ini di rombel tersebut.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran aktif tidak ditemukan pada sekolah ini.',
            'rombel_id.exists' => 'Rombel tidak ditemukan pada sekolah ini.',
            'category.required' => 'Kategori penilaian wajib dipilih.',
            'title.required' => 'Judul penilaian wajib diisi.',
            'max_score.required' => 'Nilai maksimal wajib diisi.',
        ];
    }
}
