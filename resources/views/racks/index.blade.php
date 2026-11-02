@extends('layouts.app')
@section('title', 'Lokasi Rak & Box Arsip')
@section('page_title', 'Lokasi Rak & Box Arsip')

@section('content')
<div>
    <div style="margin-bottom:20px;">
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-main);">Tata Letak Rak & Box Arsip Gudang</h2>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">Visualisasi penyimpanan fisik dokumen PB di setiap rak gudang</p>
    </div>

    <div class="rack-layout-grid">
        @foreach($rakList as $rak)
        <div class="rack-widget">
            <div class="rack-widget-header" style="border-top:3px solid {{ $rak['warna'] }};">
                <div>
                    <div style="font-weight:800;font-size:1rem;color:var(--text-main);">{{ $rak['kode'] }}</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">{{ $rak['nama'] }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.4rem;font-weight:900;color:{{ $rak['warna'] }};">{{ $rak['kapasitas'] }}%</div>
                    <div style="font-size:0.72rem;color:var(--text-muted);">kapasitas</div>
                </div>
            </div>
            <div class="rack-progress-bar" style="margin:0 0 12px;">
                <div class="rack-progress-fill" style="width:{{ $rak['kapasitas'] }}%;background:{{ $rak['warna'] }};"></div>
            </div>

            {{-- Dokumen di rak ini --}}
            @if(isset($docsByRak[$rak['kode']]) && $docsByRak[$rak['kode']]->count() > 0)
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($docsByRak[$rak['kode']] as $doc)
                    <div class="rack-doc-item">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span class="rak-location-badge" style="font-size:0.7rem;">{{ $doc->nomor_box }}</span>
                            <span class="status-pill {{ $doc->status_class }}" style="font-size:0.68rem;padding:2px 7px;">{{ Str::limit($doc->status, 18) }}</span>
                        </div>
                        <div style="font-family:monospace;font-size:0.75rem;font-weight:700;color:#0369a1;margin-top:4px;">
                            <a href="{{ route('documents.show', $doc) }}" style="color:#0369a1;text-decoration:none;">
                                {{ $doc->nomor_pb }}
                            </a>
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                            {{ $doc->nama_pemohon }} &bull; {{ Str::limit($doc->bagian, 25) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center;padding:24px;color:var(--text-light);font-size:0.82rem;">
                    Belum ada dokumen di rak ini
                </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
