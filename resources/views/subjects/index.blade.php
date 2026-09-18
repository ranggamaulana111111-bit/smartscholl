@extends('layouts.app')

@section('title', 'Mata Pelajaran')
@section('content')
<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-text" style="letter-spacing:-0.02em">Mata Pelajaran</h1>
            <p class="text-sm text-text-muted mt-1">Kelola daftar mata pelajaran.</p>
        </div>
        <a href="{{ route('subjects.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah
        </a>
    </div>

    @if($subjects->isEmpty())
        <div class="card p-12 text-center">
            <p class="text-text-muted">Belum ada mata pelajaran.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-12">No</th>
                            <th>Kode Mapel</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>Guru Pengajar</th>
                            <th>Kelas</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $i => $subject)
                            <tr>
                                <td class="text-text-muted">{{ $subjects->firstItem() + $i }}</td>
                                <td class="text-text-muted">{{ $subject->code ?? '-' }}</td>
                                <td class="font-semibold text-text">{{ $subject->name }}</td>
                                <td class="max-w-xs">
                                    @forelse($subject->teacher_names ?? [] as $teacher)
                                        <span class="badge mb-1">{{ $teacher }}</span>
                                    @empty
                                        <span class="text-text-muted">-</span>
                                    @endforelse
                                </td>
                                <td class="max-w-xs">
                                    @forelse($subject->rombel_names ?? [] as $rombel)
                                        <span class="badge mb-1">{{ $rombel }}</span>
                                    @empty
                                        <span class="text-text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="{{ $subject->is_active ? 'badge-success' : 'badge-danger' }}">
                                        {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('subjects.show', $subject) }}" class="text-text-muted hover:text-accent mr-3 transition-colors duration-fast">Detail</a>
                                    <a href="{{ route('subjects.edit', $subject) }}" class="text-text-muted hover:text-accent mr-3 transition-colors duration-fast">Edit</a>
                                    <form method="POST" action="{{ route('subjects.destroy', $subject) }}" class="inline" onsubmit="return confirm('Hapus mata pelajaran ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger hover:text-red-700 transition-colors duration-fast">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-border">{{ $subjects->links() }}</div>
        </div>
    @endif
</div>
@endsection