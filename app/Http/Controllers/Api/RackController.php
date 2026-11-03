<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Box;
use App\Models\Rack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RackController extends Controller
{
    public function index()
    {
        $racks = Rack::with(['boxes.documents.items', 'boxes.lastAuditedByUser'])->get()->map(function ($rack) {
            return [
                'id'                   => $rack->id,
                'kode_rak'             => $rack->kode_rak,
                'nama_rak'             => $rack->nama_rak,
                'zona_lokasi'          => $rack->zona_lokasi,
                'warna_label'          => $rack->warna_label,
                'baris'                => $rack->baris,
                'kolom'                => $rack->kolom,
                'keterangan'           => $rack->keterangan,
                'total_dokumen'        => $rack->total_dokumen,
                'kapasitas_maksimal'   => $rack->kapasitas_maksimal,
                'persentase_kapasitas' => $rack->persentase_kapasitas,
                'status_kapasitas'     => $rack->status_kapasitas,
                'boxes'                => $rack->boxes->map(function ($box) {
                    return [
                        'id'                     => $box->id,
                        'nomor_box'              => $box->nomor_box,
                        'baris_ke'               => $box->baris_ke,
                        'kolom_ke'               => $box->kolom_ke,
                        'kapasitas_maksimal'     => $box->kapasitas_maksimal,
                        'status'                 => $box->status,
                        'catatan'                => $box->catatan,
                        'last_audited_at'        => $box->last_audited_at?->toIso8601String(),
                        'last_audited_formatted' => $box->last_audited_formatted,
                        'last_audited_by'        => $box->lastAuditedByUser?->name,
                        'is_recently_audited'    => $box->is_recently_audited,
                        'total_dokumen'          => $box->total_dokumen,
                        'persentase_kapasitas'   => $box->persentase_kapasitas,
                        'sisa_kapasitas'         => $box->sisa_kapasitas,
                        'is_warning'             => $box->is_warning,
                        'is_full'                => $box->is_full,
                        'has_borrowed_docs'      => $box->has_borrowed_docs,
                        'visual_status_class'    => $box->visual_status_class,
                        'documents'              => $box->documents->map(function ($d) {
                            return [
                                'id'                => $d->id,
                                'nomor_pb'          => $d->nomor_pb,
                                'tanggal_indonesia' => $d->tanggal_indonesia,
                                'bagian'            => $d->bagian,
                                'nama_pemohon'      => $d->nama_pemohon,
                                'status'            => $d->status,
                                'status_class'      => $d->status_class,
                                'is_borrowed'       => str_contains($d->status, 'Dipinjam'),
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($racks);
    }

    public function boxDocuments(Box $box)
    {
        $box->load(['rack', 'lastAuditedByUser', 'documents.items', 'documents.attachments', 'documents.activeBorrowing']);

        return response()->json([
            'box' => [
                'id'                     => $box->id,
                'nomor_box'              => $box->nomor_box,
                'rack_id'                => $box->rack_id,
                'rack_kode'              => $box->rack?->kode_rak,
                'rack_nama'              => $box->rack?->nama_rak,
                'zona_lokasi'            => $box->rack?->zona_lokasi,
                'baris_ke'               => $box->baris_ke,
                'kolom_ke'               => $box->kolom_ke,
                'total_dokumen'          => $box->total_dokumen,
                'kapasitas_maksimal'     => $box->kapasitas_maksimal,
                'persentase_kapasitas'   => $box->persentase_kapasitas,
                'sisa_kapasitas'         => $box->sisa_kapasitas,
                'status'                 => $box->status,
                'catatan'                => $box->catatan,
                'last_audited_at'        => $box->last_audited_at?->toIso8601String(),
                'last_audited_formatted' => $box->last_audited_formatted,
                'last_audited_by'        => $box->lastAuditedByUser?->name,
                'is_recently_audited'    => $box->is_recently_audited,
                'has_borrowed_docs'      => $box->has_borrowed_docs,
            ],
            'documents' => $box->documents->map(function ($doc) {
                return [
                    'id'                => $doc->id,
                    'nomor_pb'          => $doc->nomor_pb,
                    'tanggal_indonesia' => $doc->tanggal_indonesia,
                    'bagian'            => $doc->bagian,
                    'nama_pemohon'      => $doc->nama_pemohon,
                    'status'            => $doc->status,
                    'status_class'      => $doc->status_class,
                    'keterangan'        => $doc->keterangan,
                    'is_borrowed'       => str_contains($doc->status, 'Dipinjam'),
                    'active_borrowing'  => $doc->activeBorrowing ? [
                        'id'                  => $doc->activeBorrowing->id,
                        'nama_peminjam'       => $doc->activeBorrowing->nama_peminjam,
                        'departemen_peminjam' => $doc->activeBorrowing->departemen_peminjam,
                        'tanggal_pinjam'      => $doc->activeBorrowing->tanggal_pinjam?->format('d/m/Y'),
                        'batas_kembali'       => $doc->activeBorrowing->batas_kembali?->format('d/m/Y'),
                        'is_overdue'          => $doc->activeBorrowing->is_overdue,
                    ] : null,
                    'total_items'       => $doc->items->count(),
                    'total_harga'       => $doc->total_harga,
                ];
            }),
        ]);
    }

    public function toggleLock(Box $box)
    {
        $user = auth()->user();
        if ($user && !$user->canManageWarehouse()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak (HTTP 403): Hanya Admin/Petugas Warehouse yang berhak mengubah status penguncian box.',
            ], 403);
        }

        $newStatus = $box->status === 'Penuh (Terkunci)' ? 'Tersedia' : 'Penuh (Terkunci)';
        $box->update(['status' => $newStatus]);

        ActivityLog::log(
            'box_status',
            "Mengubah status {$box->nomor_box} pada {$box->rack?->kode_rak} menjadi {$newStatus}"
        );

        return response()->json([
            'success'    => true,
            'status'     => $newStatus,
            'is_locked'  => $newStatus === 'Penuh (Terkunci)',
            'message'    => "Status {$box->nomor_box} berhasil diubah menjadi {$newStatus}.",
        ]);
    }

    /**
     * Sahkan hasil Stock Opname / Audit Fisik Ordner Box
     */
    public function commitAudit(Request $request, Box $box)
    {
        $user = auth()->user();
        if ($user && !$user->canManageWarehouse()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak (HTTP 403): Hanya Admin/Petugas Warehouse yang berhak mengesahkan Stock Opname Box.',
            ], 403);
        }

        $validated = $request->validate([
            'matched_pbs'   => 'nullable|array',
            'matched_pbs.*' => 'string',
            'missing_pbs'   => 'nullable|array',
            'missing_pbs.*' => 'string',
            'misplaced_pbs' => 'nullable|array',
            'misplaced_pbs.*' => 'string',
            'catatan'       => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($box, $validated, $request) {
            $user = auth()->user();
            $box->update([
                'last_audited_at'        => now(),
                'last_audited_by_user_id'=> $user?->id,
            ]);

            $matchedCount = count($validated['matched_pbs'] ?? []);
            $missingCount = count($validated['missing_pbs'] ?? []);
            $misplacedCount = count($validated['misplaced_pbs'] ?? []);
            $auditorName = $user?->name ?? 'Petugas Gudang';

            ActivityLog::create([
                'user_id'       => $user?->id,
                'activity_type' => 'stock_opname',
                'subject_type'  => Box::class,
                'subject_id'    => $box->id,
                'description'   => "Stock Opname fisik ordner {$box->nomor_box} ({$box->rack?->kode_rak}): {$matchedCount} Cocok, {$missingCount} Belum Ter-scan, {$misplacedCount} Salah Box oleh {$auditorName}",
                'properties'    => [
                    'box_id'          => $box->id,
                    'nomor_box'       => $box->nomor_box,
                    'rack_kode'       => $box->rack?->kode_rak,
                    'matched_count'   => $matchedCount,
                    'missing_count'   => $missingCount,
                    'misplaced_count' => $misplacedCount,
                    'matched_pbs'     => $validated['matched_pbs'] ?? [],
                    'missing_pbs'     => $validated['missing_pbs'] ?? [],
                    'misplaced_pbs'   => $validated['misplaced_pbs'] ?? [],
                    'catatan'         => $validated['catatan'] ?? null,
                    'audited_at'      => now()->toIso8601String(),
                ],
                'ip_address'    => $request->ip(),
            ]);

            $box->refresh();
            $box->load(['rack', 'lastAuditedByUser']);

            return response()->json([
                'success' => true,
                'message' => "Stock Opname untuk {$box->nomor_box} ({$box->rack?->kode_rak}) berhasil disahkan.",
                'box'     => [
                    'id'                     => $box->id,
                    'nomor_box'              => $box->nomor_box,
                    'rack_kode'              => $box->rack?->kode_rak,
                    'last_audited_at'        => $box->last_audited_at?->toIso8601String(),
                    'last_audited_formatted' => $box->last_audited_formatted,
                    'last_audited_by'        => $auditorName,
                    'is_recently_audited'    => $box->is_recently_audited,
                ],
            ]);
        });
    }
}
