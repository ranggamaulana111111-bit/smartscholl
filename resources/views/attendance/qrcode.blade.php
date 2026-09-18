@extends('layouts.app')

@section('title', 'QR: ' . $student->name)
@section('content')
<div class="max-w-sm mx-auto print:max-w-none print:m-0">
    <div class="mb-6 print:hidden">
        <h1 class="font-display text-2xl font-semibold text-forest-950">Kartu QR Siswa</h1>
        <p class="text-sm text-forest-700/70 mt-1">Cetak kartu ini sebagai kartu pelajar ber-QR.</p>
    </div>

    <div class="bg-white border border-forest-200 rounded-lg p-6 text-center print:border print:rounded print:p-4 flex flex-col items-center gap-4">
        <p class="text-sm font-medium text-forest-800">{{ auth()->user()->tenant->name ?? 'Smart School' }}</p>
        <div class="w-36 h-36 flex items-center justify-center border border-forest-100 bg-paper-50 rounded">
            {!! $qr !!}
        </div>
        <p class="font-display text-lg font-semibold text-forest-900">{{ $student->name }}</p>
        <p class="text-sm text-forest-800">NISN: {{ $student->nisn }}</p>
        <p class="text-sm text-forest-700/70">Rombel: {{ $student->rombel?->name ?? '-' }}</p>
    </div>

    <div class="mt-5 flex justify-center print:hidden">
        <button onclick="window.print()" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
            Cetak Kartu
        </button>
    </div>
</div>

<style>
    @media print {
        aside, nav { display: none !important; }
        main { padding: 0 !important; }
        body { margin: 0; }
    }
</style>
@endsection