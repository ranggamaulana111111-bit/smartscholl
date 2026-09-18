@extends('layouts.app')

@section('title', 'Cetak QR Siswa')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-forest-950">Kartu QR Siswa</h1>
    <p class="text-sm text-forest-700/70 mt-1">QR berisi token unik siswa, untuk scanner kehadiran gerbang.</p>
</div>

<div class="bg-paper-50 border border-forest-100 rounded-md p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($students as $item)
            @php $student = $item['student']; @endphp
            <div class="bg-white border border-forest-100 rounded-md p-5 text-center flex flex-col items-center gap-3">
                <div class="w-28 h-28 flex items-center justify-center border border-forest-100 bg-paper-50 rounded">
                    {!! $item['qr'] !!}
                </div>
                <div>
                    <p class="font-medium text-forest-900 text-sm">{{ $student->name }}</p>
                    <p class="text-xs text-forest-700/70 mt-0.5">NISN: {{ $student->nisn }}</p>
                    <p class="text-xs text-forest-700/60">{{ $student->rombel?->name ?? '-' }}</p>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center">
                <p class="text-forest-700/70">Belum ada data siswa.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6 flex justify-center">
        <button onclick="window.print()" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
            Cetak Semua Kartu QR
        </button>
    </div>
</div>

<style>
    @media print {
        aside, nav, .print\:hidden { display: none !important; }
        main { padding: 0 !important; }
        .bg-paper-50 { background: white !important; border: none !important; }
        .bg-white { page-break-inside: avoid; }
    }
</style>
@endsection