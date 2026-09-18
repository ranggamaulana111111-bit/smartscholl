@extends('layouts.app')

@section('title', 'Manajemen Rombel')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-forest-950">Manajemen Rombel</h1>
        <p class="text-sm text-forest-700/70 mt-1">Pembagian rombongan belajar per tahun ajaran.</p>
    </div>
    <a href="{{ route('rombels.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Rombel
    </a>
</div>

@if($rombels->isEmpty())
    <div class="p-12 bg-paper-50 border border-forest-100 rounded-md text-center">
        <p class="text-forest-700/70">Belum ada data rombel.</p>
    </div>
@else
    <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                        <th class="px-4 py-3 font-medium w-12">No</th>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Tingkat</th>
                        <th class="px-4 py-3 font-medium">Tahun Ajaran</th>
                        <th class="px-4 py-3 font-medium">Wali Kelas</th>
                        <th class="px-4 py-3 font-medium">Siswa</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-50">
                    @foreach($rombels as $i => $rombel)
                        <tr class="hover:bg-forest-50/50 transition-colors">
                            <td class="px-4 py-3 text-forest-700">{{ $rombels->firstItem() + $i }}</td>
                            <td class="px-4 py-3 font-medium">{{ $rombel->name }}</td>
                            <td class="px-4 py-3">{{ $rombel->grade_level }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $rombel->academicYear?->name }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $rombel->homeroomTeacher?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-100 text-forest-800 text-xs font-medium">{{ $rombel->students_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('rombels.show', $rombel) }}" class="text-forest-700 hover:text-gold-600 mr-3 transition-colors">Detail</a>
                                <a href="{{ route('rombels.edit', $rombel) }}" class="text-forest-700 hover:text-gold-600 mr-3 transition-colors">Edit</a>
                                <form method="POST" action="{{ route('rombels.destroy', $rombel) }}" class="inline" onsubmit="return confirm('Hapus rombel ini?');">
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
            {{ $rombels->links() }}
        </div>
    </div>
@endif
@endsection