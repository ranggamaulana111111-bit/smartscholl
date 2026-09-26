@extends('layouts.app')

@section('title', 'Cetak QR Siswa')
@section('content')
<x-page-head title="Kartu QR Siswa"
    description="QR berisi token unik siswa, untuk scanner kehadiran gerbang."
    eyebrow="Absensi">
</x-page-head>

<div class="bg-white border border-border rounded-md p-6 print:border-none print:p-0 print:shadow-none">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 card-grid">
        @forelse($students as $item)
            @php $student = $item['student']; @endphp
            <div class="qr-card">
                <div class="qr-card__qr">
                    {!! $item['qr'] !!}
                </div>
                <div class="flex-1 min-w-0 text-left">
                    <p class="qr-card__school">{{ auth()->user()->tenant->name ?? 'Smart School' }}</p>
                    <div class="qr-card__sep"></div>
                    <p class="qr-card__name">{{ $student->name }}</p>
                    <p class="qr-card__row"><span>NISN</span><strong class="font-mono">{{ $student->nisn }}</strong></p>
                    <p class="qr-card__row"><span>Rombel</span><strong>{{ $student->rombel?->name ?? '-' }}</strong></p>
                    <p class="mt-2 print:hidden">
                        <a href="{{ route('attendance.qrcode', $student) }}" class="text-xs font-medium text-text underline decoration-accent underline-offset-4 hover:text-accent-deep transition-colors">Cetak kartu ini &rarr;</a>
                    </p>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center">
                <p class="text-text-muted/70">Belum ada data siswa.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6 flex justify-center print:hidden">
        <button onclick="window.print()" class="btn btn-primary">
            Cetak Semua Kartu QR
        </button>
    </div>
</div>

<style>
    @media print {
        @page {
            size: 210mm 297mm;
            margin: 5mm;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        aside, nav { display: none !important; }
        main { padding: 0 !important; }

        .card-grid {
            grid-template-columns: repeat(2, 85.6mm) !important;
            gap: 5mm !important;
        }

        .card-grid .qr-card,
        .card-grid .qr-card::after,
        .card-grid .qr-card__qr {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .card-grid .qr-card {
            width: 85.6mm !important;
            height: 53.98mm !important;
            max-width: none;
            margin: 0;
            padding: 3.5mm 4mm;
            gap: 4mm;
            border-radius: 3.18mm;
            border: 0.25mm solid var(--color-border);
            box-shadow: none;
            break-inside: avoid;
        }

        .card-grid .qr-card::after {
            height: 1.2mm;
            background: var(--color-accent);
        }

        .card-grid .qr-card__qr {
            width: 26mm;
            height: 26mm;
            padding: 1mm;
            border-radius: 2mm;
        }

        .card-grid .qr-card__school { font-size: 2.2mm; }
        .card-grid .qr-card__name { font-size: 3.4mm; }
        .card-grid .qr-card__row { font-size: 2.4mm; }
        .card-grid .qr-card__row span { font-size: 2mm; }
        .card-grid .qr-card__sep { margin: 1mm 0; }
    }
</style>
@endsection