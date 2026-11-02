<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'nomor_pb', 'tanggal_pb', 'bagian', 'nama_pemohon', 'nip_pemohon',
        'keterangan', 'status', 'lokasi_rak', 'nomor_box',
        'dokumen', 'dokumen_size', 'dokumen_path', 'catatan_arsip', 'uploaded_by',
    ];

    protected $casts = [
        'tanggal_pb' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function getTotalHargaAttribute(): int
    {
        return $this->items->sum(fn($item) => $item->jumlah * $item->harga_satuan);
    }

    public function getStatusClassAttribute(): string
    {
        if (str_contains($this->status, 'Terverifikasi')) return 'pill-verified';
        if (str_contains($this->status, 'Menunggu')) return 'pill-pending';
        if (str_contains($this->status, 'Dipinjam')) return 'pill-borrowed';
        if (str_contains($this->status, 'Selesai')) return 'pill-done';
        return 'pill-pending';
    }

    public function getTanggalIndonesiaAttribute(): string
    {
        if (!$this->tanggal_pb) return '-';
        $bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        return $this->tanggal_pb->day . ' ' . $bulan[$this->tanggal_pb->month - 1] . ' ' . $this->tanggal_pb->year;
    }
}
