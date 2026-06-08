<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan DKM</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
            color: #555;
        }
        .meta {
            margin-bottom: 15px;
            font-size: 9px;
            color: #777;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .masuk {
            color: #16a34a;
        }
        .keluar {
            color: #dc2626;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 8px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        .summary-box {
            display: inline-block;
            border: 1px solid #ddd;
            padding: 8px 15px;
            margin: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEUANGAN DKM</h1>
        <p>Buku Kas Umum</p>
        <p>Periode: {{ $periodLabel }}</p>
    </div>

    <div class="meta">
        Dicetak pada: {{ $generatedAt }}
    </div>

    {{-- Buku Kas Umum Table --}}
    <div class="section-title">Arus Kas</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 8%">Tanggal</th>
                <th style="width: 12%">No. Transaksi</th>
                <th style="width: 20%">Uraian</th>
                <th style="width: 10%">Kategori</th>
                <th class="text-right" style="width: 12%">Masuk (Rp)</th>
                <th class="text-right" style="width: 12%">Keluar (Rp)</th>
                <th class="text-right" style="width: 12%">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td class="text-center">{{ $row['tanggal'] }}</td>
                <td>{{ $row['nomor_transaksi'] }}</td>
                <td>{{ $row['nama'] }}</td>
                <td>{{ $row['kategori'] }}</td>
                <td class="text-right masuk">{{ $row['masuk'] > 0 ? number_format($row['masuk'], 0, ',', '.') : '-' }}</td>
                <td class="text-right keluar">{{ $row['keluar'] > 0 ? number_format($row['keluar'], 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada transaksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Rekap Pemasukan --}}
    <div class="section-title">Rekapitulasi Pemasukan</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%">Kategori</th>
                <th class="text-center" style="width: 20%">Jumlah Transaksi</th>
                <th class="text-right" style="width: 30%">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap['pemasukan']['detail'] as $item)
            <tr>
                <td>{{ $item['kategori'] }}</td>
                <td class="text-center">{{ $item['jumlah_transaksi'] }}</td>
                <td class="text-right masuk">{{ number_format($item['total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total Pemasukan</td>
                <td class="text-right masuk">{{ number_format($rekap['pemasukan']['total'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Rekap Pengeluaran --}}
    <div class="section-title">Rekapitulasi Pengeluaran</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%">Kategori</th>
                <th class="text-center" style="width: 20%">Jumlah Transaksi</th>
                <th class="text-right" style="width: 30%">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap['pengeluaran']['detail'] as $item)
            <tr>
                <td>{{ $item['kategori'] }}</td>
                <td class="text-center">{{ $item['jumlah_transaksi'] }}</td>
                <td class="text-right keluar">{{ number_format($item['total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total Pengeluaran</td>
                <td class="text-right keluar">{{ number_format($rekap['pengeluaran']['total'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Selisih --}}
    <table>
        <tr class="total-row">
            <td style="width: 70%"><strong>Selisih (Pemasukan - Pengeluaran)</strong></td>
            <td class="text-right" style="width: 30%">
                <strong>Rp {{ number_format($rekap['selisih'], 0, ',', '.') }}</strong>
            </td>
        </tr>
    </table>

</body>
</html>
