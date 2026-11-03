<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    protected $fillable = [
        'document_id', 'jenis_dokumen', 'file_name', 'file_path', 'file_size', 'mime_type',
    ];

    protected $appends = [
        'is_pdf',
        'is_image',
        'preview_url',
        'download_url',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function getIsPdfAttribute(): bool
    {
        return str_contains(strtolower($this->mime_type ?? ''), 'pdf') || str_ends_with(strtolower($this->file_name), '.pdf');
    }

    public function getIsImageAttribute(): bool
    {
        return str_contains(strtolower($this->mime_type ?? ''), 'image') ||
            (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $this->file_name);
    }

    public function getPreviewUrlAttribute(): string
    {
        return url("/api/attachments/{$this->id}/preview");
    }

    public function getDownloadUrlAttribute(): string
    {
        return url("/api/attachments/{$this->id}/download");
    }
}
