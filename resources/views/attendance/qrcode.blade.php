@extends('layouts.app')

@section('title', 'QR: ' . $student->name)
@section('content')
<div class="max-w-sm mx-auto print:max-w-none print:m-0">
    <div class="print:hidden mb-6">
        <a href="{{ route('attendance.qrcodes') }}" class="link">&larr; Daftar kartu</a>
        <h1 class="font-display text-2xl font-semibold text-text mt-3">Kartu QR Siswa</h1>
        <p class="text-sm text-text-muted mt-1">Cetak kartu ini sebagai kartu pelajar ber-QR.</p>
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

    <div class="mt-5 flex flex-col items-center gap-3 print:hidden">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            Cetak Kartu
        </button>
        <p class="text-xs text-text-muted text-center max-w-xs">
            Ukuran kartu 85,6 &times; 54 mm, sama seperti kartu ATM. Saat menyimpan PDF, biarkan
            <strong>Skala 100%</strong>, <strong>Margin Default</strong>, dan nyalakan <strong>Grafik latar</strong>.
        </p>
        <p class="text-xs text-text-muted text-center max-w-xs">
            Mencetak ke printer biasa? Atur kertas di dialog cetak ke <strong>85,6 &times; 54 mm</strong>
            (atau A4 tanpa fit ke halaman), lalu potong mengikuti garis kartu.
        </p>
    </div>
</div>

<style>
    @page {
        size: 85.6mm 53.98mm;
        margin: 0;
    }

    @media print {
        html, body {
            width: 85.6mm;
            height: 53.98mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #ffffff;
        }

        .skip-link,
        aside,
        nav,
        .site-header { display: none !important; }

        .app-shell { min-height: 0 !important; }

        .app-content { margin-left: 0 !important; }

        main {
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
        }

        .page-enter {
            animation: none;
            opacity: 1;
            transform: none;
        }

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