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
use Illuminate\Support\Facades\DB;
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
        } elseif ($quick === 'need_revision') {
            $query->where('status', 'like', '%Revisi%');
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
                'is_pending_physical'=> str_contains($doc->status, 'Menunggu'),
                'is_need_revision'   => str_contains($doc->status, 'Revisi'),
                'verified_at'        => $doc->verified_at ? $doc->verified_at->format('d/m/Y H:i') : null,
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
            'verifiedByUser',
        ]);

        return response()->json([
            'id'                   => $document->id,
            'nomor_pb'             => $document->nomor_pb,
            'tanggal_pb'           => $document->tanggal_pb->format('Y-m-d'),
            'tanggal_indonesia'    => $document->tanggal_indonesia,
            'bagian'               => $document->bagian,
            'nama_pemohon'         => $document->nama_pemohon,
            'nip_pemohon'          => $document->nip_pemohon,
            'keterangan'           => $document->keterangan,
            'status'               => $document->status,
            'status_class'         => $document->status_class,
            'lokasi_rak'           => $document->lokasi_rak_formatted,
            'nomor_box'            => $document->nomor_box_formatted,
            'catatan_arsip'        => $document->catatan_arsip,
            'uploaded_by'          => $document->uploaded_by ?? $document->user?->name ?? 'Admin',
            'verified_at'          => $document->verified_at ? $document->verified_at->format('d/m/Y H:i') : null,
            'verified_by'          => $document->verifiedByUser?->name,
            'kondisi_berkas_fisik' => $document->kondisi_berkas_fisik,
            'catatan_verifikasi'   => $document->catatan_verifikasi,
            'is_pending_physical'  => str_contains($document->status, 'Menunggu'),
            'is_need_revision'     => str_contains($document->status, 'Revisi'),
            'total_harga'          => $document->total_harga,
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
            $dokumenPath = $file->store('documents', 'local');
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
                    $attPath = $attFile->store('attachments', 'local');
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

        ActivityLog::log(
            'create',
            "Membuat dan mengarsipkan dokumen PB {$doc->nomor_pb} ({$doc->bagian})",
            $doc,
            [
                'nomor_pb'     => $doc->nomor_pb,
                'bagian'       => $doc->bagian,
                'nama_pemohon' => $doc->nama_pemohon,
                'status'       => $doc->status,
                'lokasi_rak'   => $doc->lokasi_rak_formatted,
                'nomor_box'    => $doc->nomor_box_formatted,
            ]
        );

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
        } elseif ($document->status === 'Perlu Revisi Fisik') {
            // Document under revision can be updated and resubmitted to 'Menunggu Arsip Fisik'
            $newStatus = $validated['status'] ?? 'Menunggu Arsip Fisik';
            if (!in_array($newStatus, ['Menunggu Arsip Fisik', 'Perlu Revisi Fisik'])) {
                $newStatus = 'Menunggu Arsip Fisik';
            }
            $validated['status'] = $newStatus;
        } else {
            $newStatus = $validated['status'] ?? $document->status;
            if (str_contains($newStatus, 'Dipinjam')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status "Dokumen Dipinjam" hanya dapat diaktifkan melalui menu Sirkulasi Peminjaman.',
                ], 422);
            }
            if (!in_array($newStatus, ['Menunggu Arsip Fisik', 'Terverifikasi & Diarsipkan', 'Selesai Pengadaan', 'Perlu Revisi Fisik'])) {
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
            if (Storage::disk('local')->exists($att->file_path)) {
                Storage::disk('local')->delete($att->file_path);
            }
            if (Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }
        if ($document->dokumen_path) {
            if (Storage::disk('local')->exists($document->dokumen_path)) {
                Storage::disk('local')->delete($document->dokumen_path);
            }
            if (Storage::disk('public')->exists($document->dokumen_path)) {
                Storage::disk('public')->delete($document->dokumen_path);
            }
        }

        $document->delete();

        ActivityLog::log('delete', "Menghapus dokumen PB {$nomor} beserta berkas fisiknya", null, ['nomor_pb' => $nomor]);

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

    public function verifyPhysical(Request $request, Document $document)
    {
        // Guard 1: Only pending physical documents can be verified
        if (!str_contains($document->status, 'Menunggu')) {
            return response()->json([
                'success' => false,
                'message' => "Dokumen {$document->nomor_pb} sudah dalam status '{$document->status}' sehingga tidak memerlukan verifikasi fisik awal.",
            ], 422);
        }

        $validated = $request->validate([
            'box_id'               => 'nullable|integer|exists:boxes,id',
            'lokasi_rak'           => 'required_without:box_id|nullable|string',
            'nomor_box'            => 'required_without:box_id|nullable|string',
            'kondisi_berkas_fisik' => 'required|string|in:Asli & Cap Basah Lengkap,Salinan Resmi Terlegalisir,Sebagian Dokumen Asli',
            'cek_tanda_tangan'     => 'required|boolean|accepted',
            'cek_lampiran_lengkap' => 'required|boolean',
            'catatan_verifikasi'   => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($validated, $document, $user) {
            // Pessimistic Locking (lockForUpdate) to prevent over-capacity race conditions
            $boxQuery = Box::with('rack')->lockForUpdate();

            if (!empty($validated['box_id'])) {
                $box = $boxQuery->find($validated['box_id']);
            } else {
                $box = $boxQuery->whereHas('rack', fn($q) => $q->where('kode_rak', $validated['lokasi_rak']))
                    ->where('nomor_box', $validated['nomor_box'])
                    ->first();
            }

            if (!$box) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box yang dipilih tidak ditemukan pada rak gudang.',
                ], 422);
            }

            if ($box->status === 'Penuh (Terkunci)') {
                return response()->json([
                    'success' => false,
                    'message' => "Box {$box->nomor_box} pada {$box->rack->kode_rak} sedang dikunci/penuh. Silakan pilih box lain.",
                ], 422);
            }

            if ($box->sisa_kapasitas <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Kapasitas Box {$box->nomor_box} sudah penuh (maksimal {$box->kapasitas_maksimal} map). Silakan pilih box lain.",
                ], 422);
            }

            $oldValues = [
                'status'     => $document->status,
                'lokasi_rak' => $document->lokasi_rak_formatted,
                'nomor_box'  => $document->nomor_box_formatted,
                'box_id'     => $document->box_id,
            ];

            // Normalize location dynamically from Eloquent relation
            $rackKode = $box->rack->kode_rak;
            $boxNomor = $box->nomor_box;

            $document->update([
                'status'               => 'Terverifikasi & Diarsipkan',
                'lokasi_rak'           => $rackKode,
                'nomor_box'            => $boxNomor,
                'box_id'               => $box->id,
                'verified_at'          => now(),
                'verified_by_user_id'  => $user?->id,
                'kondisi_berkas_fisik' => $validated['kondisi_berkas_fisik'],
                'catatan_verifikasi'   => $validated['catatan_verifikasi'] ?? null,
                'catatan_arsip'        => $document->catatan_arsip
                    ? $document->catatan_arsip . "\n[Verifikasi Fisik " . now()->format('d/m/Y') . " by " . ($user->name ?? 'Admin') . "]: " . ($validated['catatan_verifikasi'] ?? 'Sesuai fisik')
                    : $validated['catatan_verifikasi'],
            ]);

            // Auto-lock box if capacity reaches maximum
            if ($box->fresh()->total_dokumen >= $box->kapasitas_maksimal) {
                $box->update(['status' => 'Penuh (Terkunci)']);
            }

            $newValues = [
                'status'               => 'Terverifikasi & Diarsipkan',
                'lokasi_rak'           => $rackKode,
                'nomor_box'            => $boxNomor,
                'box_id'               => $box->id,
                'kondisi_berkas_fisik' => $validated['kondisi_berkas_fisik'],
                'verified_by'          => $user?->name ?? 'Admin',
            ];

            ActivityLog::log(
                'verify_physical',
                "Verifikasi fisik dokumen PB {$document->nomor_pb} oleh " . ($user->name ?? 'Admin') . " dan pengarsipan pada {$rackKode} - {$boxNomor} (Kondisi: {$validated['kondisi_berkas_fisik']})",
                $document,
                [
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'checklist'  => [
                        'cek_tanda_tangan'     => $validated['cek_tanda_tangan'],
                        'cek_lampiran_lengkap' => $validated['cek_lampiran_lengkap'],
                    ],
                ]
            );

            return response()->json([
                'success'  => true,
                'document' => $document->fresh(['box.rack', 'verifiedByUser']),
                'message'  => "Dokumen PB {$document->nomor_pb} berhasil diverifikasi dan resmi disimpan pada {$rackKode} - {$boxNomor}.",
            ]);
        });
    }

    /**
     * Reject or return physical document for revision before warehouse archive.
     */
    public function rejectPhysical(Request $request, Document $document)
    {
        // Guard: Only pending physical documents can be rejected/returned for revision
        if (!str_contains($document->status, 'Menunggu')) {
            return response()->json([
                'success' => false,
                'message' => "Hanya dokumen dengan status 'Menunggu Arsip Fisik' yang dapat ditolak atau dikembalikan untuk revisi fisik.",
            ], 422);
        }

        $validated = $request->validate([
            'alasan_penolakan' => 'required|string|min:3|max:1000',
        ]);

        $user = Auth::user();

        $oldValues = [
            'status'             => $document->status,
            'catatan_verifikasi' => $document->catatan_verifikasi,
        ];

        $rejectionNote = "[Revisi Fisik " . now()->format('d/m/Y H:i') . " by " . ($user->name ?? 'Admin') . "]: " . $validated['alasan_penolakan'];

        $document->update([
            'status'             => 'Perlu Revisi Fisik',
            'catatan_verifikasi' => $validated['alasan_penolakan'],
            'catatan_arsip'      => $document->catatan_arsip
                ? $document->catatan_arsip . "\n" . $rejectionNote
                : $rejectionNote,
        ]);

        $newValues = [
            'status'             => 'Perlu Revisi Fisik',
            'catatan_verifikasi' => $validated['alasan_penolakan'],
            'rejected_by'        => $user?->name ?? 'Admin',
        ];

        ActivityLog::log(
            'reject_physical',
            "Penolakan berkas fisik dokumen PB {$document->nomor_pb} oleh " . ($user->name ?? 'Admin') . ". Alasan: {$validated['alasan_penolakan']}",
            $document,
            [
                'old_values'       => $oldValues,
                'new_values'       => $newValues,
                'alasan_penolakan' => $validated['alasan_penolakan'],
            ]
        );

        return response()->json([
            'success'  => true,
            'document' => $document->fresh(['box.rack', 'verifiedByUser']),
            'message'  => "Dokumen PB {$document->nomor_pb} berhasil dikembalikan ke pemohon dengan status 'Perlu Revisi Fisik'.",
        ]);
    }

    /**
     * Relocate / Mutate physical documents from one box/rack to another.
     */
    public function relocate(Request $request)
    {
        $validated = $request->validate([
            'document_ids'   => 'required|array|min:1',
            'document_ids.*' => 'required|integer|exists:documents,id',
            'target_rack_id' => 'required|integer|exists:racks,id',
            'target_box_id'  => 'required|integer|exists:boxes,id',
            'alasan'         => 'required|string|min:3|max:500',
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($validated, $user) {
            $targetRack = Rack::findOrFail($validated['target_rack_id']);

            // Pessimistic Locking on target box
            $targetBox = Box::where('id', $validated['target_box_id'])
                ->where('rack_id', $targetRack->id)
                ->lockForUpdate()
                ->first();

            if (!$targetBox) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box tujuan tidak ditemukan pada rak yang dipilih.',
                ], 422);
            }

            if ($targetBox->status === 'Penuh (Terkunci)') {
                return response()->json([
                    'success' => false,
                    'message' => "Box {$targetBox->nomor_box} pada {$targetRack->kode_rak} sedang dikunci/penuh secara fisik. Silakan pilih box lain.",
                ], 422);
            }

            $documents = Document::whereIn('id', $validated['document_ids'])
                ->lockForUpdate()
                ->get();

            // Guard: Document cannot be borrowed or pending/revision
            foreach ($documents as $doc) {
                if (str_contains($doc->status, 'Dipinjam')) {
                    return response()->json([
                        'success' => false,
                        'message' => "Dokumen {$doc->nomor_pb} sedang dipinjam keluar gudang sehingga tidak dapat direlokasi fisik.",
                    ], 422);
                }
                if (str_contains($doc->status, 'Revisi') || str_contains($doc->status, 'Menunggu')) {
                    return response()->json([
                        'success' => false,
                        'message' => "Dokumen {$doc->nomor_pb} belum sah tersimpan di rak gudang sehingga tidak dapat direlokasi.",
                    ], 422);
                }
            }

            // Available capacity check (don't count documents already in the target box)
            $newCount = $documents->where('box_id', '!=', $targetBox->id)->count();
            if ($newCount > $targetBox->sisa_kapasitas) {
                return response()->json([
                    'success' => false,
                    'message' => "Kapasitas {$targetBox->nomor_box} tidak mencukupi. Sisa slot: {$targetBox->sisa_kapasitas}, jumlah berkas yang dipindahkan: {$newCount}.",
                ], 422);
            }

            foreach ($documents as $doc) {
                $oldValues = [
                    'box_id'     => $doc->box_id,
                    'lokasi_rak' => $doc->lokasi_rak_formatted,
                    'nomor_box'  => $doc->nomor_box_formatted,
                ];

                $newValues = [
                    'box_id'     => $targetBox->id,
                    'lokasi_rak' => $targetRack->kode_rak,
                    'nomor_box'  => $targetBox->nomor_box,
                ];

                $originLoc = "{$doc->lokasi_rak_formatted} - {$doc->nomor_box_formatted}";
                $relocateNote = "[Relokasi " . now()->format('d/m/Y') . "]: Dari {$originLoc} ke {$targetRack->kode_rak} - {$targetBox->nomor_box}. Alasan: {$validated['alasan']}";

                $doc->update([
                    'box_id'        => $targetBox->id,
                    'lokasi_rak'    => $targetRack->kode_rak,
                    'nomor_box'     => $targetBox->nomor_box,
                    'catatan_arsip' => $doc->catatan_arsip ? $doc->catatan_arsip . "\n" . $relocateNote : $relocateNote,
                ]);

                ActivityLog::log(
                    'document_relocate',
                    "Relokasi dokumen PB {$doc->nomor_pb} dari {$originLoc} ke {$targetRack->kode_rak} - {$targetBox->nomor_box} oleh " . ($user->name ?? 'Admin') . ". Alasan: {$validated['alasan']}",
                    $doc,
                    [
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'alasan'     => $validated['alasan'],
                    ]
                );
            }

            // Auto-lock target box if capacity reaches maximum (e.g. 20)
            if ($targetBox->fresh()->total_dokumen >= $targetBox->kapasitas_maksimal) {
                $targetBox->update(['status' => 'Penuh (Terkunci)']);
            }

            return response()->json([
                'success'    => true,
                'message'    => "Berhasil memindahkan {$documents->count()} dokumen ke {$targetRack->kode_rak} - {$targetBox->nomor_box}.",
                'target_box' => $targetBox->fresh(['rack', 'documents']),
            ]);
        });
    }
}
