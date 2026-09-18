@extends('layouts.app')

@section('title', 'Jadwal Mengajar')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-text">Jadwal Mengajar</h1>
        <p class="text-sm text-text-muted mt-1">{{ $year?->name ?? 'Tahun ajaran aktif belum ditentukan' }}</p>
    </div>
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
        <a href="{{ route('schedules.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Jadwal
        </a>
    @endif
</div>

@if($schedules->isEmpty())
    <div class="card p-12 text-center">
        <p class="text-text-muted">Belum ada jadwal mengajar.</p>
    </div>
@else
    @php $dayLabels = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu']; @endphp
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru</th>
                        <th>Rombel</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $s)
                        <tr>
                            <td><span class="badge">{{ $dayLabels[$s->day_of_week] ?? '-' }}</span></td>
                            <td class="text-text-muted">{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</td>
                            <td class="font-medium">{{ $s->subject->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $s->teacher->name ?? '-' }}</td>
                            <td><span class="badge">{{ $s->rombel->name ?? '-' }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
                                    <a href="{{ route('schedules.edit', $s) }}" class="text-text-muted hover:text-accent mr-3 transition-colors">Edit</a>
                                    <form method="POST" action="{{ route('schedules.destroy', $s) }}" class="inline" onsubmit="return confirm('Hapus jadwal ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger hover:opacity-80 transition-colors">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">{{ $schedules->links() }}</div>
    </div>
@endif
@endsection
