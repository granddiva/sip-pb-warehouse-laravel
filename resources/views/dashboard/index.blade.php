@extends('layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard Ringkasan')

@section('content')
{{-- Hero Banner --}}
<div class="dash-hero-banner">
    <div class="dash-hero-text">
        <h1>Pusat Pengarsipan PB Warehouse</h1>
        <p>Kelola pencarian, penyimpanan fisik, dan verifikasi dokumen Permintaan Barang (PB) antar departemen operasional PT Besmindo Materi Sewatama secara terpusat, cepat, dan akurat.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('documents.index') }}" class="btn-hero-action">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span>Cari Dokumen PB</span>
        </a>
        <a href="{{ route('documents.create') }}" class="btn-hero-action" style="background:#0284c7;color:#fff;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Upload Arsip Baru</span>
        </a>
    </div>
</div>

{{-- KPI Metric Cards --}}
<div class="metrics-grid">
    <div class="metric-card">
        <div>
            <div class="metric-label">Total Dokumen PB</div>
            <div class="metric-value">{{ $totalCount }}</div>
            <div class="metric-desc" style="color:#0284c7;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Tercatat di Database
            </div>
        </div>
        <div class="metric-icon-box metric-blue">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
    </div>
    <div class="metric-card">
        <div>
            <div class="metric-label">Terverifikasi &amp; Arsip Fisik</div>
            <div class="metric-value">{{ $verifiedCount }}</div>
            <div class="metric-desc" style="color:#059669;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Lengkap di Rak Gudang
            </div>
        </div>
        <div class="metric-icon-box metric-green">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
    <div class="metric-card">
        <div>
            <div class="metric-label">Menunggu Arsip Fisik</div>
            <div class="metric-value">{{ $pendingCount }}</div>
            <div class="metric-desc" style="color:#d97706;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Menunggu Berkas Asli
            </div>
        </div>
        <div class="metric-icon-box metric-amber">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
    <div class="metric-card">
        <div>
            <div class="metric-label">Dokumen Dipinjam</div>
            <div class="metric-value">{{ $borrowedCount }}</div>
            <div class="metric-desc" style="color:#dc2626;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Audit / Peminjaman Internal
            </div>
        </div>
        <div class="metric-icon-box metric-red">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </div>
    </div>
</div>

{{-- 2-Column Grid --}}
<div class="dash-columns-2">
    {{-- Rak Status --}}
    <div class="card-widget">
        <div class="widget-header">
            <h3 class="widget-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Status Kapasitas Rak Arsip Gudang
            </h3>
            <a href="{{ route('documents.index') }}" class="widget-link">Kelola Dokumen &rarr;</a>
        </div>
        <div class="rack-grid">
            @foreach($rakList as $rak)
            <div class="rack-card">
                <div class="rack-card-title">
                    <span>{{ $rak['kode'] }}</span>
                    <span style="color:var(--bms-blue-600)">{{ $rak['kapasitas'] }}%</span>
                </div>
                <div style="font-size:0.74rem;color:var(--text-muted);margin-bottom:8px;">{{ $rak['nama'] }}</div>
                <div class="rack-progress-bar">
                    <div class="rack-progress-fill" style="width:{{ $rak['kapasitas'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        <div style="background:#f8fafc;padding:12px 16px;border-radius:8px;font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:8px;">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="color:#0284c7">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>Setiap dokumen PB yang diunggah harus diberi label kode box dan disimpan ke rak fisik yang sesuai.</span>
        </div>
    </div>

    {{-- Dokumen Terkini --}}
    <div class="card-widget">
        <div class="widget-header">
            <h3 class="widget-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Dokumen PB Masuk Terkini
            </h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            @foreach($recentDocs as $doc)
            <div style="display:flex;align-items:flex-start;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--border-subtle);">
                <div style="width:36px;height:36px;border-radius:8px;background:#eff6ff;color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-family:monospace;font-weight:700;font-size:0.82rem;color:#0369a1;">{{ $doc->nomor_pb }}</span>
                        <span style="font-size:0.7rem;color:var(--text-light);">{{ $doc->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div style="font-size:0.78rem;color:var(--text-main);font-weight:600;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $doc->nama_pemohon }} &bull; {{ $doc->bagian }}
                    </div>
                    <div style="display:flex;gap:8px;margin-top:4px;align-items:center;">
                        <span class="rak-location-badge">{{ $doc->lokasi_rak }} - {{ $doc->nomor_box }}</span>
                        <span style="font-size:0.72rem;color:var(--text-muted);">{{ $doc->dokumen }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
