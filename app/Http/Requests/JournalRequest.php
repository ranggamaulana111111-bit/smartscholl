<?php

namespace App\Http\Requests;

use App\Models\Journal;
use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('guru');
    }

    public function rules(): array
    {
        $tenantId = currentTenantId();

        return [
            'schedule_id' => [
                'nullable',
                'integer',
                Rule::exists('schedules', 'id')->where(
                    fn ($query) => $query->where('user_id', auth()->id())
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'rombel_id' => [
                'required',
                'integer',
                Rule::exists('rombels', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'topic' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['draft', 'closed'])],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.exists' => 'Jadwal tidak ditemukan atau bukan milik Anda pada sekolah ini.',
            'subject_id.exists' => 'Mata pelajaran tidak ditemukan pada sekolah ini.',
            'rombel_id.required' => 'Rombel wajib dipilih.',
            'rombel_id.exists' => 'Rombel tidak ditemukan pada sekolah ini.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.before_or_equal' => 'Tanggal jurnal tidak boleh di masa depan.',
            'topic.required' => 'Topik / materi pembelajaran wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $scheduleId = $this->input('schedule_id');

            if (! $scheduleId) {
                $this->rejectDuplicateUnscheduledJournal($validator);

                return;
            }

            $schedule = Schedule::find($scheduleId);

            if (! $schedule) {
                return;
            }

            if ((int) $schedule->user_id !== (int) auth()->id()) {
                $validator->errors()->add('schedule_id', 'Jadwal yang dipilih bukan milik Anda.');

                return;
            }

            if ($this->filled('subject_id') && (int) $this->input('subject_id') !== (int) $schedule->subject_id) {
                $validator->errors()->add('subject_id', 'Mata pelajaran tidak sesuai dengan jadwal yang dipilih.');
            }

            if ($this->filled('rombel_id') && (int) $this->input('rombel_id') !== (int) $schedule->rombel_id) {
                $validator->errors()->add('rombel_id', 'Rombel tidak sesuai dengan jadwal yang dipilih.');
            }

            $exists = Journal::query()
                ->where('user_id', auth()->id())
                ->where('schedule_id', $scheduleId)
                ->whereDate('date', $this->input('date'))
                ->when($this->route('journal'), fn ($q) => $q->where('id', '!=', $this->route('journal')->id))
                ->exists();

            if ($exists) {
                $validator->errors()->add('schedule_id', 'Jurnal untuk jadwal ini sudah diisi pada tanggal tersebut.');
            }
        });
    }

    /**
     * Jurnal tanpa jadwal tetap harus unik per guru, tanggal, rombel, dan mapel.
     * Pengecekan di sini mencegah error database dan memberi pesan yang jelas,
     * tanpa melarang kombinasi sah lain (mapel/rombel berbeda pada hari yang sama).
     */
    private function rejectDuplicateUnscheduledJournal($validator): void
    {
        $exists = Journal::query()
            ->where('user_id', auth()->id())
            ->whereNull('schedule_id')
            ->whereDate('date', $this->input('date'))
            ->where('rombel_id', $this->input('rombel_id'))
            ->when(
                $this->filled('subject_id'),
                fn ($query) => $query->where('subject_id', $this->input('subject_id')),
                fn ($query) => $query->whereNull('subject_id'),
            )
            ->when($this->route('journal'), fn ($q) => $q->where('id', '!=', $this->route('journal')->id))
            ->exists();

        if ($exists) {
            $validator->errors()->add('subject_id', 'Jurnal untuk rombel dan mata pelajaran ini sudah diisi pada tanggal tersebut.');
        }
    }
}
