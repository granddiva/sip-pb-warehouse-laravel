<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{
    protected $fillable = [
        'rack_id', 'nomor_box', 'baris_ke', 'kolom_ke', 'kapasitas_maksimal', 'status', 'catatan',
    ];

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getTotalDokumenAttribute(): int
    {
        return $this->documents()->count();
    }

    public function getPersentaseKapasitasAttribute(): int
    {
        $max = $this->kapasitas_maksimal ?: 20;
        return (int) min(100, round(($this->total_dokumen / $max) * 100));
    }

    public function getSisaKapasitasAttribute(): int
    {
        $max = $this->kapasitas_maksimal ?: 20;
        return max(0, $max - $this->total_dokumen);
    }

    public function getIsWarningAttribute(): bool
    {
        return $this->persentase_kapasitas >= 90;
    }

    public function getIsFullAttribute(): bool
    {
        return $this->persentase_kapasitas >= 100;
    }

    public function getHasBorrowedDocsAttribute(): bool
    {
        return $this->documents()->where('status', 'like', '%Dipinjam%')->exists();
    }

    public function getVisualStatusClassAttribute(): string
    {
        if ($this->has_borrowed_docs) return 'box-borrowed';
        if ($this->is_full) return 'box-full';
        if ($this->is_warning) return 'box-warning';
        return 'box-available';
    }
}
