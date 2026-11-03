<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $stats = [
            'total'        => BorrowingLog::count(),
            'dipinjam'     => BorrowingLog::where('status_pinjam', 'Dipinjam')->count(),
            'overdue'      => BorrowingLog::where('status_pinjam', 'Dipinjam')->where('batas_kembali', '<', $today)->count(),
            'dikembalikan' => BorrowingLog::where('status_pinjam', 'Dikembalikan')->count(),
        ];

        $query = BorrowingLog::with(['document.box.rack', 'handledByUser', 'returnedToUser']);

        // Tab filter
        $tab = $request->get('tab', 'all');
        if ($tab === 'dipinjam') {
            $query->where('status_pinjam', 'Dipinjam');
        } elseif ($tab === 'overdue') {
            $query->where('status_pinjam', 'Dipinjam')->where('batas_kembali', '<', $today);
        } elseif ($tab === 'dikembalikan') {
            $query->where('status_pinjam', 'Dikembalikan');
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_peminjam', 'like', "%{$search}%")
                  ->orWhere('departemen_peminjam', 'like', "%{$search}%")
                  ->orWhere('keperluan', 'like', "%{$search}%")
                  ->orWhereHas('document', function ($qd) use ($search) {
                      $qd->where('nomor_pb', 'like', "%{$search}%")
                         ->orWhere('nama_pemohon', 'like', "%{$search}%");
                  });
            });
        }

        $borrowings = $query->latest('tanggal_pinjam')->paginate(10);

        $borrowings->getCollection()->transform(function ($b) {
            return [
                'id'                  => $b->id,
                'document_id'         => $b->document_id,
                'nomor_pb'            => $b->document?->nomor_pb ?? '–',
                'bagian'              => $b->document?->bagian ?? '–',
                'lokasi'              => ($b->document?->lokasi_rak_formatted ?? '-') . ' - ' . ($b->document?->nomor_box_formatted ?? '-'),
                'nama_peminjam'       => $b->nama_peminjam,
                'departemen_peminjam' => $b->departemen_peminjam,
                'keperluan'           => $b->keperluan,
                'tanggal_pinjam'      => $b->tanggal_pinjam->format('d/m/Y'),
                'batas_kembali'       => $b->batas_kembali->format('d/m/Y'),
                'tanggal_kembali'     => $b->tanggal_kembali ? $b->tanggal_kembali->format('d/m/Y') : null,
                'status_pinjam'       => $b->status_pinjam,
                'is_overdue'          => $b->is_overdue,
                'sisa_hari'           => $b->sisa_hari,
                'is_form_pb_lengkap'  => $b->is_form_pb_lengkap,
                'is_lampiran_lengkap' => $b->is_lampiran_lengkap,
                'kondisi_fisik'       => $b->kondisi_fisik,
                'catatan'             => $b->catatan,
                'handled_by'          => $b->handledByUser?->name ?? 'Admin',
                'returned_to'         => $b->returnedToUser?->name,
                'slip_url'            => route('borrowings.slip', $b),
            ];
        });

        return response()->json([
            'stats'      => $stats,
            'borrowings' => $borrowings,
        ]);
    }

    public function availableDocs()
    {
        $docs = Document::where('status', 'not like', '%Dipinjam%')
            ->where('status', 'not like', '%Menunggu%')
            ->where('status', 'not like', '%Revisi%')
            ->orderBy('nomor_pb')
            ->get(['id', 'nomor_pb', 'bagian', 'nama_pemohon', 'lokasi_rak', 'nomor_box']);

        return response()->json($docs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_id'         => 'required|exists:documents,id',
            'nama_peminjam'       => 'required|string|max:255',
            'departemen_peminjam' => 'required|string|max:255',
            'keperluan'           => 'required|string|max:500',
            'tanggal_pinjam'      => 'required|date',
            'batas_kembali'       => 'required|date|after_or_equal:tanggal_pinjam',
            'catatan'             => 'nullable|string|max:1000',
        ]);

        $document = Document::findOrFail($validated['document_id']);

        if (str_contains($document->status, 'Menunggu') || str_contains($document->status, 'Revisi')) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen belum dapat dipinjam karena berkas fisik belum disahkan di rak gudang.',
            ], 422);
        }

        if (str_contains($document->status, 'Dipinjam')) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen ini sedang dipinjam oleh pihak lain.',
            ], 422);
        }

        $oldStatus = $document->status;

        $borrowing = BorrowingLog::create([
            'document_id'         => $document->id,
            'handled_by'          => auth()->id(),
            'nama_peminjam'       => $validated['nama_peminjam'],
            'departemen_peminjam' => $validated['departemen_peminjam'],
            'keperluan'           => $validated['keperluan'],
            'tanggal_pinjam'      => $validated['tanggal_pinjam'],
            'batas_kembali'       => $validated['batas_kembali'],
            'status_pinjam'       => 'Dipinjam',
            'catatan'             => $validated['catatan'] ?? null,
        ]);

        $document->update([
            'status' => 'Dokumen Dipinjam',
        ]);

        ActivityLog::log(
            'borrow',
            "Memproses peminjaman dokumen fisik {$document->nomor_pb} oleh {$borrowing->nama_peminjam} ({$borrowing->departemen_peminjam})",
            $document,
            [
                'borrowing_id'        => $borrowing->id,
                'nama_peminjam'       => $borrowing->nama_peminjam,
                'departemen_peminjam' => $borrowing->departemen_peminjam,
                'keperluan'           => $borrowing->keperluan,
                'tanggal_pinjam'      => $borrowing->tanggal_pinjam->format('Y-m-d'),
                'batas_kembali'       => $borrowing->batas_kembali->format('Y-m-d'),
                'old_values'          => [
                    'status' => $oldStatus,
                ],
                'new_values'          => [
                    'status' => 'Dokumen Dipinjam',
                ],
            ]
        );

        return response()->json([
            'success'   => true,
            'borrowing' => $borrowing,
            'message'   => "Peminjaman dokumen {$document->nomor_pb} berhasil dicatat.",
        ]);
    }

    public function returnDoc(Request $request, BorrowingLog $borrowing)
    {
        $validated = $request->validate([
            'tanggal_kembali'     => 'required|date',
            'is_form_pb_lengkap'  => 'required|boolean',
            'is_lampiran_lengkap' => 'required|boolean',
            'kondisi_fisik'       => 'required|string',
            'catatan_kembali'     => 'nullable|string|max:1000',
        ]);

        $auditNotes = "[BAP Verifikasi Fisik Pengembalian - " . Carbon::parse($validated['tanggal_kembali'])->format('d/m/Y') . "]\n"
            . "• Formulir Asli PB: " . ($validated['is_form_pb_lengkap'] ? 'Lengkap & Utuh' : 'TIDAK LENGKAP / HILANG') . "\n"
            . "• Dokumen Pendukung (PO/DO/BAST): " . ($validated['is_lampiran_lengkap'] ? 'Lengkap' : 'ADA LAMPIRAN TERCECER') . "\n"
            . "• Kondisi Fisik Berkas: " . $validated['kondisi_fisik'];

        if (!empty($validated['catatan_kembali'])) {
            $auditNotes .= "\n• Keterangan Petugas: " . $validated['catatan_kembali'];
        }

        $oldDocStatus = $borrowing->document?->status;
        $oldBorrowingStatus = $borrowing->status_pinjam;

        $borrowing->update([
            'status_pinjam'       => 'Dikembalikan',
            'tanggal_kembali'     => $validated['tanggal_kembali'],
            'is_form_pb_lengkap'  => $validated['is_form_pb_lengkap'],
            'is_lampiran_lengkap' => $validated['is_lampiran_lengkap'],
            'kondisi_fisik'       => $validated['kondisi_fisik'],
            'returned_to_user_id' => auth()->id(),
            'catatan'             => $borrowing->catatan ? $borrowing->catatan . "\n\n" . $auditNotes : $auditNotes,
        ]);

        if ($borrowing->document) {
            $borrowing->document->update([
                'status' => 'Terverifikasi & Diarsipkan',
            ]);

            $isProblematic = !$validated['is_form_pb_lengkap'] || !$validated['is_lampiran_lengkap'] || $validated['kondisi_fisik'] !== 'Baik & Utuh';

            ActivityLog::log(
                $isProblematic ? 'return_warning' : 'return',
                "Penerimaan dan verifikasi berkas {$borrowing->document->nomor_pb} dari {$borrowing->nama_peminjam}. Status fisik: {$validated['kondisi_fisik']}",
                $borrowing->document,
                [
                    'borrowing_id' => $borrowing->id,
                    'old_values'   => [
                        'document_status'  => $oldDocStatus,
                        'borrowing_status' => $oldBorrowingStatus,
                    ],
                    'new_values'   => [
                        'document_status'  => 'Terverifikasi & Diarsipkan',
                        'borrowing_status' => 'Dikembalikan',
                        'kondisi_fisik'    => $validated['kondisi_fisik'],
                    ],
                    'checklist'    => [
                        'is_form_pb_lengkap'  => $validated['is_form_pb_lengkap'],
                        'is_lampiran_lengkap' => $validated['is_lampiran_lengkap'],
                    ],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Dokumen {$borrowing->document?->nomor_pb} berhasil diverifikasi dan dikembalikan ke arsip rak gudang.",
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'rekap_sirkulasi_peminjaman_' . date('Ymd_His') . '.csv';

        $borrowings = BorrowingLog::with(['document.box.rack', 'handledByUser'])
            ->latest('tanggal_pinjam')
            ->get();

        ActivityLog::log('export', "Mengekspor rekap {$borrowings->count()} data peminjaman dokumen ke Excel/CSV");

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($borrowings) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No.',
                'Nomor PB',
                'Departemen PB',
                'Lokasi Arsip Fisik',
                'Nama Peminjam',
                'Departemen Peminjam',
                'Keperluan',
                'Tanggal Pinjam',
                'Batas Kembali',
                'Tanggal Dikembalikan',
                'Status Peminjaman',
                'Kondisi Overdue',
                'Petugas Penyerah',
                'Petugas Penerima Kembali',
                'Kondisi Fisik Berkas',
                'Kelengkapan Dokumen',
                'Catatan',
            ]);

            $no = 1;
            foreach ($borrowings as $b) {
                fputcsv($handle, [
                    $no++,
                    $b->document?->nomor_pb ?? '-',
                    $b->document?->bagian ?? '-',
                    ($b->document?->lokasi_rak_formatted ?? '-') . ' - ' . ($b->document?->nomor_box_formatted ?? '-'),
                    $b->nama_peminjam,
                    $b->departemen_peminjam,
                    $b->keperluan,
                    $b->tanggal_pinjam->format('Y-m-d'),
                    $b->batas_kembali->format('Y-m-d'),
                    $b->tanggal_kembali ? $b->tanggal_kembali->format('Y-m-d') : '-',
                    $b->status_pinjam,
                    $b->is_overdue ? 'TERLAMBAT' : 'Normal',
                    $b->handledByUser?->name ?? 'Admin',
                    $b->returnedToUser?->name ?? ($b->tanggal_kembali ? 'Petugas' : '-'),
                    $b->kondisi_fisik ?? ($b->tanggal_kembali ? 'Baik & Utuh' : '-'),
                    $b->tanggal_kembali ? ($b->is_form_pb_lengkap && $b->is_lampiran_lengkap ? 'Lengkap' : 'Perlu Diperiksa') : '-',
                    $b->catatan ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
