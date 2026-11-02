@extends('layouts.app')
@section('title', 'Data Dokumen PB')
@section('page_title', 'Data Dokumen PB')

@section('content')
<div>
    {{-- Header Actions --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-main);">Data Arsip Dokumen PB</h2>
            <p style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">
                Menampilkan {{ $documents->total() }} dokumen Permintaan Barang yang telah diarsipkan
            </p>
        </div>
        <a href="{{ route('documents.create') }}" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Upload Dokumen PB Baru
        </a>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="filter-bar-card">
        <form method="GET" action="{{ route('documents.index') }}" id="filterForm">
            <div class="filter-grid">
                <div class="search-input-wrap">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" id="searchInput" class="search-input"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Cari No. PB, nama pemohon, rincian barang...">
                </div>
                <input type="date" name="tanggal" class="filter-select" value="{{ $filters['tanggal'] ?? '' }}"
                       title="Filter Tanggal PB">
                <select name="bagian" class="filter-select">
                    <option value="">Semua Bagian / Dept.</option>
                    @foreach($bagianList as $b)
                        <option value="{{ $b }}" {{ ($filters['bagian'] ?? '') === $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    @foreach($statusList as $s)
                        <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn-primary" style="flex:1;">Cari</button>
                    <a href="{{ route('documents.index') }}" class="btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Document Table --}}
    <div class="doc-table-card">
        @if($documents->count() === 0)
            <div style="text-align:center;padding:60px 20px;color:var(--text-muted);">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 16px;opacity:0.3;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p style="font-weight:600;">Tidak ada dokumen ditemukan</p>
                <p style="font-size:0.82rem;margin-top:4px;">Coba ubah filter pencarian atau upload dokumen baru</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nomor PB</th>
                        <th>Tanggal</th>
                        <th>Bagian / Dept.</th>
                        <th>Pemohon</th>
                        <th>Rak / Box</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $i => $doc)
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.8rem;">{{ $documents->firstItem() + $i }}</td>
                        <td>
                            <span style="font-family:monospace;font-size:0.82rem;font-weight:700;color:#0369a1;">
                                {{ $doc->nomor_pb }}
                            </span>
                            @if($doc->dokumen)
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">
                                📎 {{ $doc->dokumen }}
                            </div>
                            @endif
                        </td>
                        <td style="font-size:0.82rem;white-space:nowrap;">{{ $doc->tanggal_indonesia }}</td>
                        <td>
                            <span class="bagian-badge">{{ $doc->bagian }}</span>
                        </td>
                        <td>
                            <div style="font-size:0.82rem;font-weight:600;">{{ $doc->nama_pemohon }}</div>
                            <div style="font-size:0.72rem;color:var(--text-muted);">{{ $doc->nip_pemohon }}</div>
                        </td>
                        <td>
                            @if($doc->lokasi_rak)
                            <span class="rak-location-badge">{{ $doc->lokasi_rak }} – {{ $doc->nomor_box }}</span>
                            @else
                            <span style="color:var(--text-light);font-size:0.8rem;">–</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill {{ $doc->status_class }}">{{ $doc->status }}</span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="{{ route('documents.show', $doc) }}" class="btn-action btn-detail" title="Detail">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('documents.edit', $doc) }}" class="btn-action btn-edit" title="Edit">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('Hapus dokumen PB {{ $doc->nomor_pb }}? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-top:1px solid var(--border-subtle);flex-wrap:wrap;gap:8px;">
            <div style="font-size:0.8rem;color:var(--text-muted);">
                Menampilkan {{ $documents->firstItem() }}–{{ $documents->lastItem() }} dari {{ $documents->total() }} dokumen
            </div>
            <div>{{ $documents->links() }}</div>
        </div>
        @endif
    </div>
</div>
@endsection
