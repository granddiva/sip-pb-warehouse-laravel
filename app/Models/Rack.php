<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Rack extends Model
{
    protected $fillable = [
        'kode_rak', 'nama_rak', 'zona_lokasi', 'warna_label', 'baris', 'kolom', 'keterangan',
    ];

    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class);
    }

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(Document::class, Box::class);
    }

    public function getTotalDokumenAttribute(): int
    {
        return $this->documents()->count();
    }

    public function getKapasitasMaksimalAttribute(): int
    {
        return (int) $this->boxes->sum('kapasitas_maksimal');
    }

    public function getPersentaseKapasitasAttribute(): int
    {
        $max = $this->kapasitas_maksimal;
        if ($max <= 0) return 0;
        return (int) min(100, round(($this->total_dokumen / $max) * 100));
    }

    public function getStatusKapasitasAttribute(): string
    {
        $persen = $this->persentase_kapasitas;
        if ($persen >= 90) return 'Hampir Penuh';
        if ($persen >= 50) return 'Sedang';
        return 'Tersedia';
    }
}
