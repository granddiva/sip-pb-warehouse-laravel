@extends('layouts.app')
@section('title', 'Detail Dokumen PB')
@section('page_title', 'Detail Dokumen PB')

@section('content')
<div style="max-width:800px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-main);">Detail Dokumen PB</h2>
            <p style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">
                <span style="font-family:monospace;font-weight:700;color:#0369a1;">{{ $document->nomor_pb }}</span>
            </p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('documents.edit', $document) }}" class="btn-secondary" style="display:inline-flex;align-items:center;gap:6px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <a href="{{ route('documents.index') }}" class="btn-secondary">← Kembali</a>
        </div>
    </div>

    {{-- Info Utama --}}
    <div class="detail-card">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Nomor PB</div>
                <div class="detail-value" style="font-family:monospace;color:#0369a1;font-weight:700;">{{ $document->nomor_pb }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tanggal PB</div>
                <div class="detail-value">{{ $document->tanggal_indonesia }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Bagian / Departemen</div>
                <div class="detail-value">{{ $document->bagian }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status Arsip</div>
                <div class="detail-value">
                    <span class="status-pill {{ $document->status_class }}">{{ $document->status }}</span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Nama Pemohon</div>
                <div class="detail-value">{{ $document->nama_pemohon }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">NIP Pemohon</div>
                <div class="detail-value">{{ $document->nip_pemohon ?? '–' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Lokasi Rak / Box</div>
                <div class="detail-value">
                    @if($document->lokasi_rak)
                        <span class="rak-location-badge">{{ $document->lokasi_rak }} – {{ $document->nomor_box }}</span>
                    @else –
                    @endif
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Diunggah oleh</div>
                <div class="detail-value">{{ $document->uploaded_by ?? '–' }}</div>
            </div>
            <div class="detail-item" style="grid-column:1/-1;">
                <div class="detail-label">Keterangan</div>
                <div class="detail-value">{{ $document->keterangan ?? '–' }}</div>
            </div>
            @if($document->catatan_arsip)
            <div class="detail-item" style="grid-column:1/-1;">
                <div class="detail-label">Catatan Arsip Fisik</div>
                <div class="detail-value" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px;font-size:0.83rem;">
                    {{ $document->catatan_arsip }}
                </div>
            </div>
            @endif
            @if($document->dokumen)
            <div class="detail-item" style="grid-column:1/-1;">
                <div class="detail-label">File Dokumen</div>
                <div class="detail-value" style="display:flex;align-items:center;gap:10px;">
                    <span>📎 {{ $document->dokumen }}</span>
                    <span style="color:var(--text-muted);font-size:0.8rem;">{{ $document->dokumen_size }}</span>
                    @if($document->dokumen_path)
                    <a href="{{ Storage::url($document->dokumen_path) }}" target="_blank" class="btn-secondary" style="font-size:0.78rem;padding:4px 10px;">
                        Download
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Rincian Barang --}}
    @if($document->items->count() > 0)
    <div class="detail-card" style="margin-top:20px;">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:16px;color:var(--text-main);">
            Rincian Barang yang Diminta ({{ $document->items->count() }} item)
        </h3>
        <div class="table-responsive">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Barang</th>
                        <th>Part Number</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th>Spesifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($document->items as $i => $item)
                    <tr>
                        <td style="color:var(--text-muted);">{{ $i+1 }}</td>
                        <td style="font-weight:600;">{{ $item->nama }}</td>
                        <td style="font-family:monospace;font-size:0.8rem;color:#0369a1;">{{ $item->part_number ?? '–' }}</td>
                        <td>{{ number_format($item->jumlah) }}</td>
                        <td>{{ $item->satuan }}</td>
                        <td>Rp {{ number_format($item->harga_satuan) }}</td>
                        <td style="font-weight:700;">Rp {{ number_format($item->total) }}</td>
                        <td style="font-size:0.8rem;color:var(--text-muted);">{{ $item->spesifikasi ?? '–' }}</td>
                    </tr>
                    @endforeach
                    <tr style="background:#f8fafc;font-weight:800;">
                        <td colspan="6" style="text-align:right;padding-right:16px;">Total Keseluruhan:</td>
                        <td>Rp {{ number_format($document->total_harga) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
