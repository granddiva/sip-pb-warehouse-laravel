@extends('layouts.app')
@section('title', 'Edit Dokumen PB')
@section('page_title', 'Edit Dokumen PB')

@section('content')
<div style="max-width:860px;">
    <div style="margin-bottom:20px;">
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-main);">Edit Dokumen PB</h2>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">
            Memperbarui data arsip untuk: <strong style="color:#0369a1;">{{ $document->nomor_pb }}</strong>
        </p>
    </div>

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#b91c1c;font-size:0.84rem;">
        <strong>Harap perbaiki kesalahan berikut:</strong>
        <ul style="margin:6px 0 0 18px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        @include('documents._form', ['bagianList' => $bagianList, 'statusList' => $statusList, 'rakList' => $rakList, 'document' => $document])

        <div style="display:flex;gap:12px;margin-top:24px;">
            <button type="submit" class="btn-primary" style="flex:1;justify-content:center;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Perubahan
            </button>
            <a href="{{ route('documents.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
