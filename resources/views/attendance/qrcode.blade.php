@extends('layouts.app')

@section('title', 'QR: ' . $student->name)
@section('content')
<div class="max-w-sm mx-auto print:max-w-none print:m-0">
    <div class="mb-6 flex items-start justify-between gap-4 print:hidden">
        <div>
            <a href="{{ route('attendance.qrcodes') }}" class="text-sm text-text-muted hover:text-accent transition-colors">&larr; Daftar kartu</a>
            <h1 class="font-display text-2xl font-semibold text-text mt-2">Kartu QR Siswa</h1>
            <p class="text-sm text-text-muted mt-1">Cetak kartu ini sebagai kartu pelajar ber-QR.</p>
        </div>
    </div>

    <div class="qr-card">
        <div class="qr-card__qr">
            {!! $qr !!}
        </div>
        <div class="flex-1 min-w-0 text-left">
            <p class="qr-card__school">{{ auth()->user()->tenant->name ?? 'Smart School' }}</p>
            <div class="qr-card__sep"></div>
            <p class="qr-card__name">{{ $student->name }}</p>
            <p class="qr-card__row"><span>NISN</span><strong class="font-mono">{{ $student->nisn }}</strong></p>
            <p class="qr-card__row"><span>Rombel</span><strong>{{ $student->rombel?->name ?? '-' }}</strong></p>
        </div>
    </div>

    <div class="mt-5 flex flex-col items-center gap-2 print:hidden">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            Cetak Kartu
        </button>
        <p class="text-xs text-text-muted">Kartu tercetak seukuran kartu ATM (85,6 &times; 54 mm).</p>
    </div>
</div>

<style>
    @media print {
        @page {
            size: 85.6mm 53.98mm;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        aside, nav { display: none !important; }
        main { padding: 0 !important; }

        .qr-card,
        .qr-card::after,
        .qr-card__qr {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .qr-card {
            width: 85.6mm;
            height: 53.98mm;
            max-width: none;
            margin: 0;
            padding: 3.5mm 4mm;
            gap: 4mm;
            border-radius: 3.18mm;
            border: 0.25mm solid var(--color-border);
            box-shadow: none;
        }

        .qr-card::after {
            height: 1.2mm;
            background: var(--color-accent);
        }

        .qr-card__qr {
            width: 28mm;
            height: 28mm;
            padding: 1mm;
            border-radius: 2mm;
        }

        .qr-card__school { font-size: 2.4mm; }
        .qr-card__name { font-size: 3.8mm; }
        .qr-card__row { font-size: 2.6mm; }
        .qr-card__row span { font-size: 2.2mm; }
        .qr-card__sep { margin: 1.2mm 0; }
    }
</style>
@endsection