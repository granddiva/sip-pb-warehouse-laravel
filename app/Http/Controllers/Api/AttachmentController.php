<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Store new attachment securely in private storage.
     */
    public function store(Request $request, Document $document)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|max:100',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $this->formatFileSize($file->getSize());
        $mimeType = $file->getMimeType();

        // Store privately on local disk (storage/app/private/attachments)
        $path = $file->store('attachments', 'local');

        $attachment = $document->attachments()->create([
            'jenis_dokumen' => $request->jenis_dokumen,
            'file_name'     => $fileName,
            'file_path'     => $path,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
        ]);

        ActivityLog::log(
            'upload_attachment',
            "Mengunggah lampiran {$attachment->jenis_dokumen} ({$fileName}) pada dokumen {$document->nomor_pb}",
            $document,
            [
                'attachment_id' => $attachment->id,
                'jenis_dokumen' => $attachment->jenis_dokumen,
                'file_name'     => $fileName,
                'file_size'     => $fileSize,
            ]
        );

        return response()->json([
            'success'    => true,
            'attachment' => $attachment->fresh(),
            'message'    => "Lampiran {$fileName} berhasil diunggah secara aman.",
        ]);
    }

    /**
     * Securely stream/preview attachment inline for authenticated users.
     */
    public function preview(DocumentAttachment $attachment)
    {
        $path = $this->resolveFilePath($attachment->file_path);

        if (!$path || !file_exists($path)) {
            abort(404, 'File lampiran tidak ditemukan pada server arsip.');
        }

        $mime = $attachment->mime_type ?: (mime_content_type($path) ?: 'application/octet-stream');

        return response()->file($path, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->file_name) . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    /**
     * Securely download attachment for authenticated users.
     */
    public function download(DocumentAttachment $attachment)
    {
        $path = $this->resolveFilePath($attachment->file_path);

        if (!$path || !file_exists($path)) {
            abort(404, 'File lampiran tidak ditemukan pada server arsip.');
        }

        return response()->download($path, $attachment->file_name);
    }

    /**
     * Delete attachment from private/public storage and database.
     */
    public function destroy(DocumentAttachment $attachment)
    {
        $doc = $attachment->document;
        $name = $attachment->file_name;

        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        ActivityLog::log(
            'delete_attachment',
            "Menghapus lampiran {$name} dari dokumen " . ($doc?->nomor_pb ?? ''),
            $doc,
            [
                'attachment_id' => $attachment->id,
                'file_name'     => $name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Lampiran {$name} berhasil dihapus.",
        ]);
    }

    private function resolveFilePath(string $filePath): ?string
    {
        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->path($filePath);
        }
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->path($filePath);
        }
        if (file_exists(storage_path('app/' . $filePath))) {
            return storage_path('app/' . $filePath);
        }
        return null;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
