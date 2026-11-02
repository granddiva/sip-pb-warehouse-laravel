@extends('layouts.app')
@section('title', 'Upload Dokumen PB')
@section('page_title', 'Upload Dokumen PB Baru')

@section('content')
<div style="max-width:860px;">
    <div style="margin-bottom:20px;">
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-main);">Upload / Arsipkan Dokumen PB Baru</h2>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">Isi formulir di bawah untuk mendaftarkan dokumen Permintaan Barang ke sistem arsip gudang</p>
    </div>

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#b91c1c;font-size:0.84rem;">
        <strong>Harap perbaiki kesalahan berikut:</strong>
        <ul style="margin:6px 0 0 18px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
        @csrf
        @include('documents._form', ['bagianList' => $bagianList, 'statusList' => $statusList, 'rakList' => $rakList])

        <div style="display:flex;gap:12px;margin-top:24px;">
            <button type="submit" class="btn-primary" style="flex:1;justify-content:center;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Simpan & Arsipkan Dokumen PB
            </button>
            <a href="{{ route('documents.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
