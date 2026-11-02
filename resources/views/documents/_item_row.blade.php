<div class="item-row-grid">
    <input type="text" name="items[{{ $idx }}][nama]" class="form-input"
           placeholder="Nama barang *" value="{{ old("items.{$idx}.nama", $item->nama ?? '') }}" required>
    <input type="text" name="items[{{ $idx }}][part_number]" class="form-input"
           placeholder="Part Number" value="{{ old("items.{$idx}.part_number", $item->part_number ?? '') }}">
    <input type="number" name="items[{{ $idx }}][jumlah]" class="form-input"
           placeholder="Jumlah" value="{{ old("items.{$idx}.jumlah", $item->jumlah ?? 1) }}" min="1">
    <input type="text" name="items[{{ $idx }}][satuan]" class="form-input"
           placeholder="Satuan" value="{{ old("items.{$idx}.satuan", $item->satuan ?? 'Pcs') }}">
    <input type="number" name="items[{{ $idx }}][harga_satuan]" class="form-input"
           placeholder="Harga Satuan (Rp)" value="{{ old("items.{$idx}.harga_satuan", $item->harga_satuan ?? 0) }}" min="0">
    <input type="text" name="items[{{ $idx }}][spesifikasi]" class="form-input"
           placeholder="Spesifikasi" value="{{ old("items.{$idx}.spesifikasi", $item->spesifikasi ?? '') }}">
    <button type="button" onclick="removeItem('item-{{ $idx }}')" class="btn-action btn-delete" style="align-self:center;" title="Hapus baris">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
