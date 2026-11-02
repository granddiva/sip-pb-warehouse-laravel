<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id', 'nama', 'part_number', 'jumlah', 'satuan', 'harga_satuan', 'spesifikasi',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function getTotalAttribute(): int
    {
        return $this->jumlah * $this->harga_satuan;
    }
}
