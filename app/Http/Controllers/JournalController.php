<?php

namespace App\Http\Controllers;

use App\Http\Requests\JournalRequest;
use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Journal::with(['subject', 'rombel', 'teacher']);

        if ($user->isGuru()) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('date') && $request->input('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->has('status') && $request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        $journals = $query->latest('date')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => $user->isGuru()
                ? Journal::where('user_id', $user->id)->count()
                : Journal::count(),
            'draft' => $user->isGuru()
                ? Journal::where('user_id', $user->id)->where('status', 'draft')->count()
                : Journal::where('status', 'draft')->count(),
            'closed' => $user->isGuru()
                ? Journal::where('user_id', $user->id)->where('status', 'closed')->count()
                : Journal::where('status', 'closed')->count(),
        ];

        return view('journals.index', compact('journals', 'stats'));
    }

    public function create(): View
    {
        $year = AcademicYear::where('is_active', true)->first();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $rombels = Rombel::when($year, fn ($q) => $q->where('academic_year_id', $year->id))->orderBy('name')->get();
        $schedules = Schedule::with(['subject', 'rombel'])
            ->where('user_id', auth()->id())
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('journals.create', compact('subjects', 'rombels', 'schedules', 'year'));
    }

    public function store(JournalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['academic_year_id'] = $data['academic_year_id'] ?? AcademicYear::where('is_active', true)->value('id');
        $data['user_id'] = auth()->id();

        $journal = Journal::create($data);

        log_audit('create', $journal);

        return to_route('journals.index')->with('success', 'Jurnal KBM berhasil disimpan.');
    }

    public function show(Journal $journal): View
    {
        $this->authorizeJournal($journal);
        $journal->load(['subject', 'rombel', 'teacher']);

        return view('journals.show', compact('journal'));
    }

    public function edit(Journal $journal): View
    {
        $this->authorizeJournal($journal);
        $year = AcademicYear::where('is_active', true)->first();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $rombels = Rombel::when($year, fn ($q) => $q->where('academic_year_id', $year->id))->orderBy('name')->get();
        $schedules = Schedule::with(['subject', 'rombel'])
            ->where('user_id', auth()->id())
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->get();

        return view('journals.edit', compact('journal', 'subjects', 'rombels', 'schedules', 'year'));
    }

    public function update(JournalRequest $request, Journal $journal): RedirectResponse
    {
        $this->authorizeJournal($journal);

        $old = $journal->only(['topic', 'status', 'date']);

        $journal->update($request->validated());

        log_audit('update', $journal, $old, $journal->only(['topic', 'status', 'date']));

        return to_route('journals.show', $journal)->with('success', 'Jurnal KBM berhasil diperbarui.');
    }

    private function authorizeJournal(Journal $journal): void
    {
        if (auth()->user()->isGuru() && $journal->user_id !== auth()->id()) {
            abort(403, 'Anda hanya dapat mengelola jurnal milik Anda sendiri.');
        }
    }
}
