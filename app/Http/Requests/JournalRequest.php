<?php

namespace App\Http\Requests;

use App\Models\Journal;
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
        return [
            'schedule_id' => ['nullable', 'exists:schedules,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'rombel_id' => ['required', 'exists:rombels,id'],
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
            'rombel_id.required' => 'Rombel wajib dipilih.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.before_or_equal' => 'Tanggal jurnal tidak boleh di masa depan.',
            'topic.required' => 'Topik / materi pembelajaran wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $scheduleId = $this->input('schedule_id');

            if ($scheduleId) {
                $exists = Journal::query()
                    ->where('user_id', auth()->id())
                    ->where('schedule_id', $scheduleId)
                    ->whereDate('date', $this->input('date'))
                    ->when($this->route('journal'), fn ($q) => $q->where('id', '!=', $this->route('journal')->id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('schedule_id', 'Jurnal untuk jadwal ini sudah diisi pada tanggal tersebut.');
                }
            }
        });
    }
}
