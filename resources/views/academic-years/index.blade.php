@extends('layouts.app')

@section('title', 'Tahun Ajaran')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-forest-950">Tahun Ajaran</h1>
        <p class="text-sm text-forest-700/70 mt-1">Periode tahun ajaran dan semester.</p>
    </div>
    <a href="{{ route('academic-years.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Tahun Ajaran
    </a>
</div>

@if($academicYears->isEmpty())
    <div class="p-12 bg-paper-50 border border-forest-100 rounded-md text-center">
        <p class="text-forest-700/70">Belum ada tahun ajaran.</p>
    </div>
@else
    <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                        <th class="px-4 py-3 font-medium w-12">No</th>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Semester</th>
                        <th class="px-4 py-3 font-medium">Periode</th>
                        <th class="px-4 py-3 font-medium">Rombel</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-50">
                    @foreach($academicYears as $i => $year)
                        <tr class="hover:bg-forest-50/50 transition-colors">
                            <td class="px-4 py-3 text-forest-700">{{ $academicYears->firstItem() + $i }}</td>
                            <td class="px-4 py-3 font-medium">{{ $year->name }}</td>
                            <td class="px-4 py-3 capitalize text-forest-700">{{ $year->semester }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $year->start_date->translatedFormat('d M Y') }} s.d. {{ $year->end_date->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $year->rombels_count }}</td>
                            <td class="px-4 py-3">
                                @if($year->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-forest-600 text-paper-50 text-xs font-medium">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-paper-200 text-forest-700 text-xs font-medium">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('academic-years.show', $year) }}" class="text-forest-700 hover:text-gold-600 mr-3 transition-colors">Detail</a>
                                <a href="{{ route('academic-years.edit', $year) }}" class="text-forest-700 hover:text-gold-600 mr-3 transition-colors">Edit</a>
                                <form method="POST" action="{{ route('academic-years.destroy', $year) }}" class="inline" onsubmit="return confirm('Hapus tahun ajaran ini? Rombel terkait ikut terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 transition-colors">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-forest-100">
            {{ $academicYears->links() }}
        </div>
    </div>
@endif
@endsection