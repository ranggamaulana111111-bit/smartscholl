@extends('layouts.app')

@section('title', 'Manajemen Guru')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-forest-950">Manajemen Guru</h1>
        <p class="text-sm text-forest-700/70 mt-1">Data guru dan tenaga pendidik.</p>
    </div>
    <a href="{{ route('teachers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Guru
    </a>
</div>

@if($teachers->isEmpty())
    <div class="p-12 bg-paper-50 border border-forest-100 rounded-md text-center">
        <p class="text-forest-700/70">Belum ada data guru.</p>
    </div>
@else
    <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                        <th class="px-4 py-3 font-medium w-12">No</th>
                        <th class="px-4 py-3 font-medium">NUPTK</th>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Mata Pelajaran</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-50">
                    @foreach($teachers as $i => $teacher)
                        <tr class="hover:bg-forest-50/50 transition-colors">
                            <td class="px-4 py-3 text-forest-700">{{ $teachers->firstItem() + $i }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $teacher->nuptk ?? '-' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $teacher->name }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $teacher->subject ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-100 text-forest-800 text-xs font-medium">
                                    {{ match($teacher->employment_status) { 'asn' => 'ASN', 'gty' => 'GTY', 'ptt' => 'PTT' } }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('teachers.show', $teacher) }}" class="text-forest-700 hover:text-gold-600 mr-3 transition-colors">Detail</a>
                                <a href="{{ route('teachers.edit', $teacher) }}" class="text-forest-700 hover:text-gold-600 mr-3 transition-colors">Edit</a>
                                <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" class="inline" onsubmit="return confirm('Hapus guru ini?');">
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
            {{ $teachers->links() }}
        </div>
    </div>
@endif
@endsection