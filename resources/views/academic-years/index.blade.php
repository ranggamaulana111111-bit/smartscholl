@extends('layouts.app')

@section('title', 'Tahun Ajaran')
@section('content')
<x-page-head
    eyebrow="Manajemen Data"
    title="Tahun Ajaran"
    description="Periode tahun ajaran dan semester berjalan."
>
    <x-slot:actions>
        <a href="{{ route('academic-years.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Tahun Ajaran
        </a>
    </x-slot:actions>
</x-page-head>

@if($academicYears->isEmpty())
    <x-panel class="p-12 text-center">
        <p class="text-text-muted">Belum ada tahun ajaran.</p>
    </x-panel>
@else
    <x-panel class="overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Nama</th>
                        <th>Semester</th>
                        <th>Periode</th>
                        <th>Rombel</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($academicYears as $i => $year)
                        <tr>
                            <td class="text-text-muted">{{ $academicYears->firstItem() + $i }}</td>
                            <td class="font-medium">{{ $year->name }}</td>
                            <td class="capitalize text-text-muted">{{ $year->semester }}</td>
                            <td class="text-text-muted">{{ $year->start_date->translatedFormat('d M Y') }} s.d. {{ $year->end_date->translatedFormat('d M Y') }}</td>
                            <td class="text-text-muted">{{ $year->rombels_count }}</td>
                            <td>
                                @if($year->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-4">
                                    <a href="{{ route('academic-years.show', $year) }}" class="link">Detail</a>
                                    <a href="{{ route('academic-years.edit', $year) }}" class="link">Edit</a>
                                    <form method="POST" action="{{ route('academic-years.destroy', $year) }}" class="inline" data-confirm="Hapus tahun ajaran ini? Rombel terkait ikut terhapus.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link link-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">
            {{ $academicYears->links() }}
        </div>
    </x-panel>
@endif
@endsection