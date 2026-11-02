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
    public function store(Request $request, Document $document)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|max:100',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $this->formatFileSize($file->getSize());
        $mimeType = $file->getMimeType();
        $path     = $file->store('attachments', 'public');

        $attachment = $document->attachments()->create([
            'jenis_dokumen' => $request->jenis_dokumen,
            'file_name'     => $fileName,
            'file_path'     => $path,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
        ]);

        ActivityLog::log(
            'upload_attachment',
            "Mengunggah lampiran {$attachment->jenis_dokumen} ({$fileName}) pada dokumen {$document->nomor_pb}"
        );

        return response()->json([
            'success'    => true,
            'attachment' => [
                'id'            => $attachment->id,
                'jenis_dokumen' => $attachment->jenis_dokumen,
                'file_name'     => $attachment->file_name,
                'file_size'     => $attachment->file_size,
                'mime_type'     => $attachment->mime_type,
                'is_pdf'        => $attachment->is_pdf,
                'is_image'      => $attachment->is_image,
                'download_url'  => route('attachments.download', $attachment),
                'preview_url'   => route('attachments.preview', $attachment),
            ],
            'message'    => "Lampiran {$fileName} berhasil diunggah.",
        ]);
    }

    public function destroy(DocumentAttachment $attachment)
    {
        $doc = $attachment->document;
        $name = $attachment->file_name;

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        ActivityLog::log('delete_attachment', "Menghapus lampiran {$name} dari dokumen " . ($doc?->nomor_pb ?? ''));

        return response()->json([
            'success' => true,
            'message' => "Lampiran {$name} berhasil dihapus.",
        ]);
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
