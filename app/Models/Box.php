<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{
    protected $fillable = [
        'rack_id', 'nomor_box', 'baris_ke', 'kolom_ke', 'kapasitas_maksimal', 'status', 'catatan',
        'last_audited_at', 'last_audited_by_user_id',
    ];

    protected $casts = [
        'last_audited_at' => 'datetime',
    ];

    protected $appends = [
        'last_audited_formatted',
        'is_recently_audited',
    ];

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function lastAuditedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_audited_by_user_id');
    }

    public function getTotalDokumenAttribute(): int
    {
        return $this->documents()->count();
    }

    public function getPersentaseKapasitasAttribute(): int
    {
        $max = $this->kapasitas_maksimal ?? 20;
        if ($max <= 0) return 100;
        return (int) min(100, round(($this->total_dokumen / $max) * 100));
    }

    public function getSisaKapasitasAttribute(): int
    {
        $max = $this->kapasitas_maksimal ?? 20;
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

    public function getLastAuditedFormattedAttribute(): ?string
    {
        if (!$this->last_audited_at) return null;
        return $this->last_audited_at->format('d M Y, H:i') . ' WIB';
    }

    public function getIsRecentlyAuditedAttribute(): bool
    {
        if (!$this->last_audited_at) return false;
        return $this->last_audited_at->diffInDays(Carbon::now()) <= 30;
    }
}
