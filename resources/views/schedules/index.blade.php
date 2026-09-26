@extends('layouts.app')

@section('title', 'Jadwal Mengajar')
@section('content')
<x-page-head title="Jadwal Mengajar"
    description="{{ $year?->name ?? 'Tahun ajaran aktif belum ditentukan' }}"
    eyebrow="Penjadwalan">
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
        <x-slot name="actions">
            <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Jadwal
            </a>
        </x-slot>
    @endif
</x-page-head>

@if($errors->any())
    <div class="panel p-4 mb-6 bg-danger/5 border-danger/20" role="alert">
        <p class="text-sm text-danger">{{ $errors->first() }}</p>
    </div>
@endif

@if($schedules->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
        </span>
        <p class="empty-state__title">Belum ada jadwal mengajar</p>
        <p class="empty-state__hint">Atur jadwal pelajaran untuk tenaga pengajar.</p>
    </div>
@else
    @php $dayLabels = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu']; @endphp
    <div class="panel overflow-hidden">
        <div class="table-wrap">
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
                            <td class="text-text-muted">{{ $s->start_time?->format('H:i') }} - {{ $s->end_time?->format('H:i') }}</td>
                            <td class="font-medium text-text">{{ $s->subject->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $s->teacher->name ?? '-' }}</td>
                            <td><span class="badge">{{ $s->rombel->name ?? '-' }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
                                    <a href="{{ route('schedules.edit', $s) }}" class="link mr-3">Edit</a>
                                    <form method="POST" action="{{ route('schedules.destroy', $s) }}" class="inline" data-confirm="Hapus jadwal ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-danger">Hapus</button>
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