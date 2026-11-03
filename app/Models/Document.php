<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    protected $fillable = [
        'user_id', 'box_id', 'nomor_pb', 'tanggal_pb', 'bagian',
        'nama_pemohon', 'nip_pemohon', 'keterangan', 'status',
        'lokasi_rak', 'nomor_box', 'dokumen', 'dokumen_size', 'dokumen_path',
        'catatan_arsip', 'uploaded_by',
        'verified_at', 'verified_by_user_id', 'kondisi_berkas_fisik', 'catatan_verifikasi',
    ];

    protected $casts = [
        'tanggal_pb'  => 'date',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function borrowingLogs(): HasMany
    {
        return $this->hasMany(BorrowingLog::class)->latest();
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(BorrowingLog::class);
    }

    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(BorrowingLog::class)->where('status_pinjam', 'Dipinjam')->latestOfMany();
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

    public function getLokasiRakFormattedAttribute(): string
    {
        return $this->box?->rack?->kode_rak ?? $this->lokasi_rak ?? '-';
    }

    public function getNomorBoxFormattedAttribute(): string
    {
        return $this->box?->nomor_box ?? $this->nomor_box ?? '-';
    }
}
