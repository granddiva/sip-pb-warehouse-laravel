<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentAttachmentController extends Controller
{
    public function store(Request $request, Document $document)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|max:100',
            'file_lampiran' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('file_lampiran');
        $fileName = $file->getClientOriginalName();
        $fileSize = $this->formatFileSize($file->getSize());
        $mimeType = $file->getMimeType();

        // Simpan berkas ke storage/app/public/attachments
        $path = $file->store('attachments', 'public');

        $attachment = $document->attachments()->create([
            'jenis_dokumen' => $request->jenis_dokumen,
            'file_name'     => $fileName,
            'file_path'     => $path,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
        ]);

        ActivityLog::log(
            'upload_attachment',
            "Mengunggah lampiran {$attachment->jenis_dokumen} ({$fileName}) untuk dokumen PB {$document->nomor_pb}"
        );

        return redirect()->back()->with('toast_success', "Lampiran {$attachment->jenis_dokumen} berhasil diunggah.");
    }

    public function destroy(DocumentAttachment $attachment)
    {
        $doc = $attachment->document;
        $jenis = $attachment->jenis_dokumen;
        $name = $attachment->file_name;

        // Hapus file fisik jika ada
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        ActivityLog::log(
            'delete_attachment',
            "Menghapus lampiran {$jenis} ({$name}) dari dokumen PB " . ($doc?->nomor_pb ?? '')
        );

        return redirect()->back()->with('toast_success', "Lampiran {$name} berhasil dihapus.");
    }

    public function download(DocumentAttachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return redirect()->back()->with('toast_warning', 'Berkas fisik lampiran tidak ditemukan pada server storage.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function preview(DocumentAttachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($attachment->file_path);
        return response()->file($fullPath, [
            'Content-Type' => $attachment->mime_type ?? 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"',
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
