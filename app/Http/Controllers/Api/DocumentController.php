<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Box;
use App\Models\Document;
use App\Models\Rack;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with(['box.rack', 'items', 'attachments']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_pb', 'like', "%{$search}%")
                  ->orWhere('nama_pemohon', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($qi) => $qi->where('nama', 'like', "%{$search}%"));
            });
        }

        // Quick filter pills
        $quick = $request->get('quick');
        if ($quick === 'today') {
            $query->whereDate('tanggal_pb', Carbon::today());
        } elseif ($quick === 'month') {
            $query->whereMonth('tanggal_pb', Carbon::today()->month)
                  ->whereYear('tanggal_pb', Carbon::today()->year);
        } elseif ($quick === 'borrowed') {
            $query->where('status', 'like', '%Dipinjam%');
        } elseif ($quick === 'pending_physical') {
            $query->where('status', 'like', '%Menunggu%');
        }

        // Dropdown filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($bagian = $request->get('bagian')) {
            $query->where('bagian', $bagian);
        }
        if ($tanggal = $request->get('tanggal')) {
            $query->whereDate('tanggal_pb', $tanggal);
        }

        $perPage = (int) $request->get('per_page', 10);
        $documents = $query->latest('tanggal_pb')->paginate($perPage);

        // Transform results with helper attributes
        $documents->getCollection()->transform(function ($doc) {
            return [
                'id'                 => $doc->id,
                'nomor_pb'           => $doc->nomor_pb,
                'tanggal_pb'         => $doc->tanggal_pb->format('Y-m-d'),
                'tanggal_indonesia'  => $doc->tanggal_indonesia,
                'bagian'             => $doc->bagian,
                'nama_pemohon'       => $doc->nama_pemohon,
                'nip_pemohon'        => $doc->nip_pemohon,
                'keterangan'         => $doc->keterangan,
                'status'             => $doc->status,
                'status_class'       => $doc->status_class,
                'lokasi_rak'         => $doc->lokasi_rak_formatted,
                'nomor_box'          => $doc->nomor_box_formatted,
                'dokumen'            => $doc->dokumen,
                'dokumen_size'       => $doc->dokumen_size,
                'total_items'        => $doc->items->count(),
                'total_attachments'  => $doc->attachments->count(),
                'total_harga'        => $doc->total_harga,
                'uploaded_by'        => $doc->uploaded_by,
            ];
        });

        return response()->json($documents);
    }

    public function show(Document $document)
    {
        $document->load([
            'items',
            'attachments',
            'box.rack',
            'borrowingLogs.handledByUser',
            'activeBorrowing.handledByUser',
            'user',
        ]);

        return response()->json([
            'id'                 => $document->id,
            'nomor_pb'           => $document->nomor_pb,
            'tanggal_pb'         => $document->tanggal_pb->format('Y-m-d'),
            'tanggal_indonesia'  => $document->tanggal_indonesia,
            'bagian'             => $document->bagian,
            'nama_pemohon'       => $document->nama_pemohon,
            'nip_pemohon'        => $document->nip_pemohon,
            'keterangan'         => $document->keterangan,
            'status'             => $document->status,
            'status_class'       => $document->status_class,
            'lokasi_rak'         => $document->lokasi_rak_formatted,
            'nomor_box'          => $document->nomor_box_formatted,
            'catatan_arsip'      => $document->catatan_arsip,
            'uploaded_by'        => $document->uploaded_by ?? $document->user?->name ?? 'Admin',
            'total_harga'        => $document->total_harga,
            'items'              => $document->items,
            'attachments'        => $document->attachments->map(fn($att) => [
                'id'            => $att->id,
                'jenis_dokumen' => $att->jenis_dokumen,
                'file_name'     => $att->file_name,
                'file_size'     => $att->file_size,
                'mime_type'     => $att->mime_type,
                'is_pdf'        => $att->is_pdf,
                'is_image'      => $att->is_image,
                'download_url'  => route('attachments.download', $att),
                'preview_url'   => route('attachments.preview', $att),
            ]),
            'active_borrowing'   => $document->activeBorrowing ? [
                'id'                  => $document->activeBorrowing->id,
                'nama_peminjam'       => $document->activeBorrowing->nama_peminjam,
                'departemen_peminjam' => $document->activeBorrowing->departemen_peminjam,
                'keperluan'           => $document->activeBorrowing->keperluan,
                'tanggal_pinjam'      => $document->activeBorrowing->tanggal_pinjam->format('d/m/Y'),
                'batas_kembali'       => $document->activeBorrowing->batas_kembali->format('d/m/Y'),
                'is_overdue'          => $document->activeBorrowing->is_overdue,
                'sisa_hari'           => $document->activeBorrowing->sisa_hari,
                'slip_url'            => route('borrowings.slip', $document->activeBorrowing),
            ] : null,
            'borrowing_history'  => $document->borrowingLogs->map(fn($b) => [
                'id'                  => $b->id,
                'nama_peminjam'       => $b->nama_peminjam,
                'departemen_peminjam' => $b->departemen_peminjam,
                'keperluan'           => $b->keperluan,
                'tanggal_pinjam'      => $b->tanggal_pinjam->format('d/m/Y'),
                'batas_kembali'       => $b->batas_kembali->format('d/m/Y'),
                'tanggal_kembali'     => $b->tanggal_kembali ? $b->tanggal_kembali->format('d/m/Y') : null,
                'status_pinjam'       => $b->status_pinjam,
                'is_overdue'          => $b->is_overdue,
                'catatan'             => $b->catatan,
                'slip_url'            => route('borrowings.slip', $b),
            ]),
            'box_id'             => $document->box_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_pb'      => 'required|string|unique:documents,nomor_pb',
            'tanggal_pb'    => 'required|date',
            'bagian'        => 'required|string',
            'nama_pemohon'  => 'required|string',
            'nip_pemohon'   => 'nullable|string',
            'keterangan'    => 'nullable|string',
            'status'        => 'required|string|in:Menunggu Arsip Fisik,Terverifikasi & Diarsipkan',
            'lokasi_rak'    => 'nullable|string',
            'nomor_box'     => 'nullable|string',
            'catatan_arsip' => 'nullable|string',
            'file_dokumen'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items'         => 'nullable|array',
        ]);

        // Resolve box_id
        $boxId = null;
        if (!empty($validated['lokasi_rak']) && !empty($validated['nomor_box'])) {
            $box = Box::whereHas('rack', fn($q) => $q->where('kode_rak', $validated['lokasi_rak']))
                      ->where('nomor_box', $validated['nomor_box'])
                      ->first();
            $boxId = $box?->id;
        }

        $dokumenName = null;
        $dokumenSize = null;
        $dokumenPath = null;
        $mimeType = null;

        if ($request->hasFile('file_dokumen')) {
            $file        = $request->file('file_dokumen');
            $dokumenName = $file->getClientOriginalName();
            $dokumenSize = round($file->getSize() / 1024, 1) . ' KB';
            $dokumenPath = $file->store('documents', 'public');
            $mimeType    = $file->getMimeType();
        }

        $user = Auth::user();

        $doc = Document::create([
            'user_id'       => $user?->id,
            'box_id'        => $boxId,
            'nomor_pb'      => $validated['nomor_pb'],
            'tanggal_pb'    => $validated['tanggal_pb'],
            'bagian'        => $validated['bagian'],
            'nama_pemohon'  => $validated['nama_pemohon'],
            'nip_pemohon'   => $validated['nip_pemohon'] ?? null,
            'keterangan'    => $validated['keterangan'] ?? null,
            'status'        => $validated['status'],
            'lokasi_rak'    => $validated['lokasi_rak'] ?? null,
            'nomor_box'     => $validated['nomor_box'] ?? null,
            'catatan_arsip' => $validated['catatan_arsip'] ?? null,
            'dokumen'       => $dokumenName,
            'dokumen_size'  => $dokumenSize,
            'dokumen_path'  => $dokumenPath,
            'uploaded_by'   => $user ? ($user->name . ' (' . $user->role . ')') : 'Admin Warehouse',
        ]);

        // Create main attachment record if file exists
        if ($dokumenPath) {
            $doc->attachments()->create([
                'jenis_dokumen' => $request->input('jenis_dokumen_utama', 'Form PB Scan Asli'),
                'file_name'     => $dokumenName,
                'file_path'     => $dokumenPath,
                'file_size'     => $dokumenSize,
                'mime_type'     => $mimeType,
            ]);
        }

        // Process additional multi-attachments if provided
        if ($request->hasFile('additional_attachments')) {
            $files = $request->file('additional_attachments');
            $types = $request->input('additional_types', []);

            foreach ($files as $idx => $attFile) {
                if ($attFile) {
                    $attName = $attFile->getClientOriginalName();
                    $attBytes = $attFile->getSize();
                    $attSize = $attBytes >= 1048576
                        ? round($attBytes / 1048576, 1) . ' MB'
                        : round($attBytes / 1024, 0) . ' KB';
                    $attPath = $attFile->store('attachments', 'public');
                    $attJenis = $types[$idx] ?? 'Dokumen Pendukung';

                    $doc->attachments()->create([
                        'jenis_dokumen' => $attJenis,
                        'file_name'     => $attName,
                        'file_path'     => $attPath,
                        'file_size'     => $attSize,
                        'mime_type'     => $attFile->getMimeType(),
                    ]);
                }
            }
        }

        // Create items
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                if (!empty($item['nama'])) {
                    $doc->items()->create([
                        'nama'         => $item['nama'],
                        'part_number'  => $item['part_number'] ?? null,
                        'jumlah'       => $item['jumlah'] ?? 1,
                        'satuan'       => $item['satuan'] ?? 'Pcs',
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'spesifikasi'  => $item['spesifikasi'] ?? null,
                    ]);
                }
            }
        }

        ActivityLog::log('create', "Membuat dan mengarsipkan dokumen PB {$doc->nomor_pb} ({$doc->bagian})");

        return response()->json([
            'success'  => true,
            'document' => $doc,
            'message'  => "Dokumen PB {$doc->nomor_pb} berhasil diarsipkan.",
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'nomor_pb'      => 'required|string|unique:documents,nomor_pb,' . $document->id,
            'tanggal_pb'    => 'required|date',
            'bagian'        => 'required|string',
            'nama_pemohon'  => 'required|string',
            'nip_pemohon'   => 'nullable|string',
            'keterangan'    => 'nullable|string',
            'status'        => 'nullable|string',
            'lokasi_rak'    => 'nullable|string',
            'nomor_box'     => 'nullable|string',
            'catatan_arsip' => 'nullable|string',
            'items'         => 'nullable|array',
        ]);

        // Enforce State Machine lifecycle
        if (str_contains($document->status, 'Dipinjam')) {
            // Cannot manually alter status while document is physically loaned out
            $validated['status'] = $document->status;
        } else {
            $newStatus = $validated['status'] ?? $document->status;
            if (str_contains($newStatus, 'Dipinjam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status "Dokumen Dipinjam" hanya dapat diaktifkan melalui menu Sirkulasi Peminjaman.',
                ], 422);
            }
            if (!in_array($newStatus, ['Menunggu Arsip Fisik', 'Terverifikasi & Diarsipkan', 'Selesai Pengadaan'])) {
                $validated['status'] = 'Terverifikasi & Diarsipkan';
            }
        }

        // Resolve box_id
        if (!empty($validated['lokasi_rak']) && !empty($validated['nomor_box'])) {
            $box = Box::whereHas('rack', fn($q) => $q->where('kode_rak', $validated['lokasi_rak']))
                      ->where('nomor_box', $validated['nomor_box'])
                      ->first();
            $validated['box_id'] = $box?->id;
        }

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $document->update($validated);

        // Replace items
        if (!empty($items)) {
            $document->items()->delete();
            foreach ($items as $item) {
                if (!empty($item['nama'])) {
                    $document->items()->create([
                        'nama'         => $item['nama'],
                        'part_number'  => $item['part_number'] ?? null,
                        'jumlah'       => $item['jumlah'] ?? 1,
                        'satuan'       => $item['satuan'] ?? 'Pcs',
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'spesifikasi'  => $item['spesifikasi'] ?? null,
                    ]);
                }
            }
        }

        ActivityLog::log('update', "Memperbarui rincian dokumen PB {$document->nomor_pb}");

        return response()->json([
            'success'  => true,
            'document' => $document,
            'message'  => "Data dokumen PB {$document->nomor_pb} berhasil diperbarui.",
        ]);
    }

    public function destroy(Document $document)
    {
        // Business rule check: cannot delete if currently borrowed
        if (str_contains($document->status, 'Dipinjam')) {
            return response()->json([
                'success' => false,
                'message' => "Dokumen {$document->nomor_pb} tidak dapat dihapus karena sedang dalam status dipinjam keluar.",
            ], 422);
        }

        $nomor = $document->nomor_pb;

        // Delete all physical attachments
        foreach ($document->attachments as $att) {
            if (Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }

        $document->delete();

        ActivityLog::log('delete', "Menghapus dokumen PB {$nomor} beserta berkas fisiknya");

        return response()->json([
            'success' => true,
            'message' => "Dokumen PB {$nomor} berhasil dihapus dari sistem.",
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'rekap_dokumen_pb_' . date('Ymd_His') . '.csv';

        $documents = Document::with(['box.rack', 'items', 'attachments'])
            ->latest('tanggal_pb')
            ->get();

        ActivityLog::log('export', "Mengekspor rekap {$documents->count()} data dokumen PB ke Excel/CSV");

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($documents) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'No.',
                'Nomor PB',
                'Tanggal PB',
                'Departemen / Bagian',
                'Nama Pemohon',
                'NIP Pemohon',
                'Keterangan',
                'Status Arsip',
                'Lokasi Rak',
                'Nomor Box',
                'Jumlah Item Barang',
                'Total Nilai PB (Rp)',
                'Jumlah Lampiran Digital',
                'Diarsipkan Oleh',
                'Tanggal Dibuat',
            ]);

            $no = 1;
            foreach ($documents as $doc) {
                fputcsv($handle, [
                    $no++,
                    $doc->nomor_pb,
                    $doc->tanggal_pb->format('Y-m-d'),
                    $doc->bagian,
                    $doc->nama_pemohon,
                    $doc->nip_pemohon ?? '-',
                    $doc->keterangan ?? '-',
                    $doc->status,
                    $doc->lokasi_rak_formatted,
                    $doc->nomor_box_formatted,
                    $doc->items->count(),
                    number_format($doc->total_harga, 0, ',', '.'),
                    $doc->attachments->count(),
                    $doc->uploaded_by ?? '-',
                    $doc->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function generateNumber(Request $request)
    {
        $bagian = $request->get('bagian', 'Warehouse & Logistik');
        $dateStr = $request->get('tanggal', date('Y-m-d'));

        $date = Carbon::parse($dateStr);
        $year = $date->format('Y');
        $month = $date->format('m');

        $acronyms = [
            'Workshop Machining & Threading' => 'BMS-MCH',
            'NDT & Tubular Inspection'       => 'BMS-NDT',
            'Warehouse & Logistik'           => 'BMS-WH',
            'HSE & K3L'                      => 'BMS-HSE',
            'Maintenance & Utility'          => 'BMS-MTN',
            'Operasional Lapangan'           => 'BMS-OPS',
            'Quality Assurance / QC'         => 'BMS-QC',
        ];

        $deptCode = $acronyms[$bagian] ?? 'BMS-GEN';
        $prefix = "PB/{$deptCode}/{$year}/{$month}/";

        // Find existing docs with same prefix to get sequence
        $count = Document::where('nomor_pb', 'like', "{$prefix}%")->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $suggested = "{$prefix}{$sequence}";

        // If by any chance suggested exists, keep incrementing
        $iter = 1;
        while (Document::where('nomor_pb', $suggested)->exists()) {
            $iter++;
            $seq = str_pad($count + $iter, 3, '0', STR_PAD_LEFT);
            $suggested = "{$prefix}{$seq}";
        }

        return response()->json([
            'suggested_nomor_pb' => $suggested,
            'dept_code'          => $deptCode,
            'year'               => $year,
            'month'              => $month,
        ]);
    }
}
