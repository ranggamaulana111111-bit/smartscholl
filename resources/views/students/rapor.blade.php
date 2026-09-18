<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapor — {{ $student->name }}</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fira-code:400" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; font-size: 13px; line-height: 1.6; color: #1a1a1a; background: #fff; padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #000; padding-bottom: 16px; margin-bottom: 24px; }
        .brand h1 { font-size: 18px; letter-spacing: -0.01em; }
        .brand p { color: #666; font-size: 12px; }
        .title { text-align: right; }
        .title h2 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px 32px; margin-bottom: 24px; }
        .field .k { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #666; }
        .field .v { font-weight: 600; font-size: 13px; }
        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 8px; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 10px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        thead th { background: #f5f5f5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }
        .num, th.n { text-align: center; }
        .score-b { color: #2ecc71; font-weight: 700; }
        .score-d { color: #e74c3c; font-weight: 700; }
        .final { font-weight: 700; }
        .badges { margin-top: 24px; display: flex; gap: 32px; }
        .badges .item { border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px 16px; text-align: center; flex: 1; }
        .badges .item p:first-child { font-size: 10px; text-transform: uppercase; color: #666; }
        .badges .item p:last-child { font-size: 18px; font-weight: 700; margin-top: 4px; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .footer .sign { width: 200px; }
        .footer .sign .line { border-top: 1px solid #1a1a1a; margin-top: 48px; padding-top: 4px; text-align: center; font-size: 11px; }
        .no-print { margin-bottom: 16px; }
        .no-print button { background: #000; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ url()->previous() }}" style="margin-left:12px;color:#666;">Kembali</a>
    </div>

    <div class="header">
        <div class="brand">
            <h1>{{ auth()->user()->tenant->name ?? '' }}</h1>
            <p>Laporan Hasil Belajar Siswa</p>
        </div>
        <div class="title">
            <h2>Rapor Semester</h2>
            <p>{{ $student->rombel->academicYear->name ?? 'Tahun Ajaran Aktif' }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="field"><p class="k">Nama Siswa</p><p class="v">{{ $student->name }}</p></div>
        <div class="field"><p class="k">NISN</p><p class="v">{{ $student->nisn }}</p></div>
        <div class="field"><p class="k">Kelas / Rombel</p><p class="v">{{ $student->rombel->name ?? '-' }}</p></div>
        <div class="field"><p class="k">Wali Kelas</p><p class="v">{{ $student->rombel->homeroomTeacher?->name ?? '-' }}</p></div>
    </div>

    <h3>Hasil Belajar</h3>
    @if($rows->isEmpty())
        <p style="color:#666;">Belum ada nilai tercatat.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:30%">Mata Pelajaran</th>
                    <th class="n">Tugas</th>
                    <th class="n">Formatif</th>
                    <th class="n">UTS</th>
                    <th class="n">UAS</th>
                    <th class="n">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="v">{{ $row['name'] }}</td>
                        @foreach(['tugas', 'formatif', 'uts', 'uas'] as $c)
                            @php $score = $row['aver'][$c] ?? null; @endphp
                            <td class="num">
                                @if($score !== null)
                                    <span class="{{ $score >= 75 ? 'score-b' : 'score-d' }}">{{ number_format($score, 2) }}</span>
                                @else
                                    <span style="color:#999;">-</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="num final">{{ $row['final_score'] !== null ? number_format($row['final_score'], 2) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Ringkasan Kehadiran</h3>
    <div class="badges">
        <div class="item"><p>Presensi</p><p>{{ $attendance['total'] }}</p></div>
        <div class="item"><p>Hadir</p><p class="score-b">{{ $attendance['hadir'] }}</p></div>
        <div class="item"><p>Sakit</p><p>{{ $attendance['sakit'] }}</p></div>
        <div class="item"><p>Izin</p><p>{{ $attendance['izin'] }}</p></div>
        <div class="item"><p>Alpha</p><p class="score-d">{{ $attendance['alpha'] }}</p></div>
    </div>

    <div class="footer">
        <div class="sign">
            <p style="font-size:11px;color:#666;">Mengetahui,<br>Kepala Sekolah</p>
            <div class="line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
        </div>
        <div class="sign">
            <p style="font-size:11px;color:#666;text-align:right;">Wali Kelas</p>
            <div class="line">{{ $student->rombel->homeroomTeacher?->name ?? '( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )' }}</div>
        </div>
    </div>
</body>
</html>