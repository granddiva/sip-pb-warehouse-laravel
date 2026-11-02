<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingLog extends Model
{
    protected $fillable = [
        'document_id', 'handled_by', 'returned_to_user_id', 'nama_peminjam', 'departemen_peminjam',
        'keperluan', 'tanggal_pinjam', 'batas_kembali', 'tanggal_kembali',
        'status_pinjam', 'is_form_pb_lengkap', 'is_lampiran_lengkap', 'kondisi_fisik', 'catatan',
    ];

    protected $casts = [
        'tanggal_pinjam'      => 'date',
        'batas_kembali'       => 'date',
        'tanggal_kembali'     => 'date',
        'is_form_pb_lengkap'  => 'boolean',
        'is_lampiran_lengkap' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function handledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function returnedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to_user_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status_pinjam === 'Dikembalikan') return false;
        return $this->batas_kembali && Carbon::today()->greaterThan($this->batas_kembali);
    }

    public function getSisaHariAttribute(): int
    {
        if (!$this->batas_kembali) return 0;
        return (int) Carbon::today()->diffInDays($this->batas_kembali, false);
    }
}
