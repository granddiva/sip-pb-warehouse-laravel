<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Peminjaman Arsip – {{ $borrowing->document?->nomor_pb ?? 'PB' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 30px;
        }
        .slip-wrapper {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 40px;
        }
        .header-kop {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #0f172a;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px;
        }
        .kop-text h1 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .kop-text p {
            font-size: 0.78rem;
            color: #475569;
            margin-top: 3px;
        }
        .doc-badge {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 6px;
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }
        .title-slip {
            text-align: center;
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 24px;
            color: #0f172a;
            text-decoration: underline;
        }
        .grid-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 20px;
        }
        .info-row {
            margin-bottom: 8px;
            font-size: 0.88rem;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
            display: inline-block;
            width: 140px;
        }
        .info-val {
            font-weight: 700;
            color: #0f172a;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 0.84rem;
        }
        .table-items th, .table-items td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        .table-items th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: left;
        }
        .terms-box {
            font-size: 0.78rem;
            color: #475569;
            border: 1px dashed #cbd5e1;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 30px;
            line-height: 1.45;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            text-align: center;
            margin-top: 30px;
        }
        .sig-space {
            height: 70px;
        }
        .sig-name {
            font-weight: 700;
            text-decoration: underline;
            font-size: 0.9rem;
        }
        .sig-role {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 2px;
        }
        .btn-print-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0284c7;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .btn-print-bar { display: none; }
            .slip-wrapper {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="btn-print-bar">
    <a href="{{ route('borrowings.index') }}" style="color:#0284c7;text-decoration:none;font-weight:600;font-size:0.9rem;">
        ← Kembali ke Daftar Sirkulasi
    </a>
    <button onclick="window.print()" class="btn-print">
        🖨️ Cetak Slip Tanda Terima (PDF / Print)
    </button>
</div>

<div class="slip-wrapper">
    <div class="header-kop">
        <div class="logo-area">
            <img src="{{ asset('images/besmindo-logo.jpg') }}" alt="Logo BMS" class="logo-img" onerror="this.style.display='none'">
            <div class="kop-text">
                <h1>PT BESMINDO MATERI SEWATAMA</h1>
                <p>Warehouse & Machining Center – Jl. Raya Duri KM 3.5, Riau - Indonesia</p>
                <p>Sistem Informasi Pengarsipan Dokumen Permintaan Barang (SIP-PB WH v2.0)</p>
            </div>
        </div>
        <div class="doc-badge">
            <div style="font-size:0.7rem;color:#64748b;">KODE TRANSAKSI</div>
            <strong>SLIP-BRW-{{ str_pad($borrowing->id, 5, '0', STR_PAD_LEFT) }}</strong>
        </div>
    </div>

    <div class="title-slip">
        SURAT TANDA TERIMA PEMINJAMAN DOKUMEN ARSIP FISIK
    </div>

    <div class="grid-info">
        <div>
            <div class="info-row">
                <span class="info-label">Nomor PB:</span>
                <span class="info-val" style="font-family:'JetBrains Mono',monospace;color:#0284c7;">
                    {{ $borrowing->document?->nomor_pb ?? '-' }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal PB:</span>
                <span class="info-val">{{ $borrowing->document?->tanggal_indonesia ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Departemen PB:</span>
                <span class="info-val">{{ $borrowing->document?->bagian ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Lokasi Rak / Box:</span>
                <span class="info-val">
                    {{ $borrowing->document?->lokasi_rak_formatted }} – {{ $borrowing->document?->nomor_box_formatted }}
                </span>
            </div>
        </div>
        <div>
            <div class="info-row">
                <span class="info-label">Nama Peminjam:</span>
                <span class="info-val">{{ $borrowing->nama_peminjam }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Departemen:</span>
                <span class="info-val">{{ $borrowing->departemen_peminjam }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Pinjam:</span>
                <span class="info-val">{{ $borrowing->tanggal_pinjam->format('d F Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Batas Kembali:</span>
                <span class="info-val" style="color:#b91c1c;">{{ $borrowing->batas_kembali->format('d F Y') }}</span>
            </div>
        </div>
    </div>

    <div style="margin-bottom:14px;font-size:0.88rem;">
        <strong>Keperluan Peminjaman:</strong>
        <div style="margin-top:4px;padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;line-height:1.45;">
            {{ $borrowing->keperluan }}
        </div>
    </div>

    @if($borrowing->document && $borrowing->document->items->count() > 0)
    <div style="margin-bottom:8px;font-size:0.86rem;font-weight:700;">Rincian Barang Terkait pada Dokumen PB:</div>
    <table class="table-items">
        <thead>
            <tr>
                <th style="width:30px;">No</th>
                <th>Nama Barang / Deskripsi</th>
                <th>Part Number</th>
                <th style="width:70px;text-align:center;">Jumlah</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($borrowing->document->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->nama }}</td>
                <td><code style="font-size:0.78rem;">{{ $item->part_number ?? '-' }}</code></td>
                <td style="text-align:center;">{{ $item->jumlah }}</td>
                <td>{{ $item->satuan ?? 'Unit' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="terms-box">
        <strong>Ketentuan Peminjaman Berkas Arsip Fisik:</strong><br>
        1. Peminjam bertanggung jawab penuh atas keutuhan, kebersihan, dan kerahasiaan berkas fisik dokumen PB.<br>
        2. Berkas fisik tidak diperkenankan diubah, dicoret, dipindahtangankan ke pihak lain tanpa persetujuan tim Warehouse.<br>
        3. Dokumen wajib dikembalikan ke gudang paling lambat pada tanggal jatuh tempo yang tertera di atas.<br>
        4. Apabila terjadi kehilangan atau kerusakan fisik dokumen, peminjam wajib membuat Berita Acara resmi.
    </div>

    <div class="signature-grid">
        <div>
            <div>Peminjam Berkas Fisik,</div>
            <div class="sig-space"></div>
            <div class="sig-name">{{ $borrowing->nama_peminjam }}</div>
            <div class="sig-role">{{ $borrowing->departemen_peminjam }}</div>
        </div>
        <div>
            <div>Petugas Pengelola Warehouse,</div>
            <div class="sig-space"></div>
            <div class="sig-name">{{ $borrowing->handledByUser?->name ?? 'Admin Warehouse' }}</div>
            <div class="sig-role">NIP: {{ $borrowing->handledByUser?->nip ?? 'BMS-WH-001' }}</div>
        </div>
    </div>
</div>

</body>
</html>
