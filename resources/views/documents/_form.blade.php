{{-- Shared form partial for create & edit --}}
@php $doc = $document ?? null; @endphp

{{-- Section 1: Info Dokumen PB --}}
<div class="upload-section">
    <div class="upload-section-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Informasi Dokumen PB
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label class="form-label" for="nomor_pb">Nomor PB <span style="color:#dc2626">*</span></label>
            <input type="text" id="nomor_pb" name="nomor_pb" class="form-input"
                   value="{{ old('nomor_pb', $doc->nomor_pb ?? '') }}"
                   placeholder="Contoh: PB/BMS-WH/2026/10/001" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="tanggal_pb">Tanggal PB <span style="color:#dc2626">*</span></label>
            <input type="date" id="tanggal_pb" name="tanggal_pb" class="form-input"
                   value="{{ old('tanggal_pb', $doc ? $doc->tanggal_pb->format('Y-m-d') : date('Y-m-d')) }}" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="bagian">Bagian / Departemen <span style="color:#dc2626">*</span></label>
            <select id="bagian" name="bagian" class="form-input" required>
                <option value="">-- Pilih Bagian --</option>
                @foreach($bagianList as $b)
                    <option value="{{ $b }}" {{ old('bagian', $doc->bagian ?? '') === $b ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="status">Status Arsip <span style="color:#dc2626">*</span></label>
            <select id="status" name="status" class="form-input" required>
                @foreach($statusList as $s)
                    <option value="{{ $s }}" {{ old('status', $doc->status ?? 'Menunggu Arsip Fisik') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="nama_pemohon">Nama Pemohon <span style="color:#dc2626">*</span></label>
            <input type="text" id="nama_pemohon" name="nama_pemohon" class="form-input"
                   value="{{ old('nama_pemohon', $doc->nama_pemohon ?? '') }}"
                   placeholder="Nama lengkap pemohon..." required>
        </div>
        <div class="form-group">
            <label class="form-label" for="nip_pemohon">NIP Pemohon</label>
            <input type="text" id="nip_pemohon" name="nip_pemohon" class="form-input"
                   value="{{ old('nip_pemohon', $doc->nip_pemohon ?? '') }}"
                   placeholder="Contoh: BMS-MCH-0104">
        </div>
    </div>

    <div class="form-group" style="margin-top:12px;">
        <label class="form-label" for="keterangan">Keterangan / Deskripsi Kebutuhan</label>
        <textarea id="keterangan" name="keterangan" class="form-input" rows="3"
                  placeholder="Jelaskan kebutuhan pengadaan barang...">{{ old('keterangan', $doc->keterangan ?? '') }}</textarea>
    </div>
</div>

{{-- Section 2: Lokasi Arsip Fisik --}}
<div class="upload-section">
    <div class="upload-section-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        Lokasi Penyimpanan Arsip Fisik
    </div>
    <div class="form-grid-2">
        <div class="form-group">
            <label class="form-label" for="lokasi_rak">Lokasi Rak</label>
            <select id="lokasi_rak" name="lokasi_rak" class="form-input">
                <option value="">-- Pilih Rak --</option>
                @foreach($rakList as $r)
                    <option value="{{ $r }}" {{ old('lokasi_rak', $doc->lokasi_rak ?? '') === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="nomor_box">Nomor Box / Ordner</label>
            <input type="text" id="nomor_box" name="nomor_box" class="form-input"
                   value="{{ old('nomor_box', $doc->nomor_box ?? '') }}"
                   placeholder="Contoh: Box 03">
        </div>
    </div>
    <div class="form-group" style="margin-top:12px;">
        <label class="form-label" for="catatan_arsip">Catatan Arsip Fisik</label>
        <textarea id="catatan_arsip" name="catatan_arsip" class="form-input" rows="2"
                  placeholder="Keterangan penyimpanan fisik...">{{ old('catatan_arsip', $doc->catatan_arsip ?? '') }}</textarea>
    </div>
</div>

{{-- Section 3: Upload File --}}
<div class="upload-section">
    <div class="upload-section-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        File Dokumen PB (Scan / PDF)
    </div>
    @if($doc && $doc->dokumen)
    <div style="background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:0.82rem;color:#047857;display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        File saat ini: <strong>{{ $doc->dokumen }}</strong> ({{ $doc->dokumen_size }}) — Upload baru untuk mengganti
    </div>
    @endif
    <label class="upload-dropzone" for="file_dokumen">
        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#94a3b8;margin-bottom:8px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        <span style="font-weight:600;color:var(--text-main);">Klik atau drag file ke sini</span>
        <span style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;">PDF, JPG, PNG — Maksimal 10 MB</span>
        <input type="file" id="file_dokumen" name="file_dokumen" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="showFileName(this)">
    </label>
    <div id="fileNameDisplay" style="font-size:0.8rem;color:#0369a1;margin-top:6px;"></div>
</div>

{{-- Section 4: Rincian Barang --}}
<div class="upload-section">
    <div class="upload-section-title" style="justify-content:space-between;">
        <span style="display:flex;align-items:center;gap:8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Rincian Barang yang Diminta
        </span>
        <button type="button" onclick="addItem()" class="btn-secondary" style="font-size:0.78rem;padding:6px 12px;">
            + Tambah Barang
        </button>
    </div>

    <div id="itemsContainer">
        @if($doc && $doc->items->count() > 0)
            @foreach($doc->items as $idx => $item)
            <div class="item-row" id="item-{{ $idx }}">
                @include('documents._item_row', ['idx' => $idx, 'item' => $item])
            </div>
            @endforeach
        @else
        <div class="item-row" id="item-0">
            @include('documents._item_row', ['idx' => 0, 'item' => null])
        </div>
        @endif
    </div>
</div>

<script>
let itemCount = {{ $doc && $doc->items->count() > 0 ? $doc->items->count() : 1 }};

function addItem() {
    const container = document.getElementById('itemsContainer');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.id = 'item-' + itemCount;
    div.innerHTML = `
        <div class="item-row-grid">
            <input type="text" name="items[${itemCount}][nama]" class="form-input" placeholder="Nama barang *" required>
            <input type="text" name="items[${itemCount}][part_number]" class="form-input" placeholder="Part Number">
            <input type="number" name="items[${itemCount}][jumlah]" class="form-input" placeholder="Jumlah" value="1" min="1">
            <input type="text" name="items[${itemCount}][satuan]" class="form-input" placeholder="Satuan (Pcs, Unit...)">
            <input type="number" name="items[${itemCount}][harga_satuan]" class="form-input" placeholder="Harga Satuan (Rp)" value="0" min="0">
            <input type="text" name="items[${itemCount}][spesifikasi]" class="form-input" placeholder="Spesifikasi / keterangan">
            <button type="button" onclick="removeItem('item-${itemCount}')" class="btn-action btn-delete" style="align-self:center;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`;
    container.appendChild(div);
    itemCount++;
}

function removeItem(id) {
    const el = document.getElementById(id);
    if (document.querySelectorAll('.item-row').length > 1) el.remove();
}

function showFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.innerHTML = '✓ File dipilih: <strong>' + input.files[0].name + '</strong>';
    }
}
</script>
