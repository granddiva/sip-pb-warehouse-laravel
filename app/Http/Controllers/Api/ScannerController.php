<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Document;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    /**
     * Resolve scanned barcode / QR code payload into actionable document or box data.
     */
    public function resolve(Request $request)
    {
        $code = trim($request->get('code', ''));

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode barcode / QR Code tidak boleh kosong.',
            ], 422);
        }

        // 1. Try URL parsing (e.g. /documents/12 or /racks?box_id=3)
        if (str_contains($code, '/documents/')) {
            $parts = explode('/documents/', $code);
            $possibleId = end($parts);
            if (is_numeric($possibleId)) {
                $doc = Document::with(['box.rack', 'items', 'attachments', 'borrowingLogs.handledByUser', 'verifiedByUser'])->find($possibleId);
                if ($doc) {
                    return $this->formatDocumentResponse($doc, $code);
                }
            }
        }

        if (str_contains($code, 'box_id=')) {
            parse_str(parse_url($code, PHP_URL_QUERY) ?? '', $queryParams);
            if (!empty($queryParams['box_id']) && is_numeric($queryParams['box_id'])) {
                $box = Box::with(['rack', 'documents'])->find($queryParams['box_id']);
                if ($box) {
                    return $this->formatBoxResponse($box, $code);
                }
            }
        }

        // 2. Direct Nomor PB Match (e.g. PB/BMS-WH/2026/09/001)
        $doc = Document::with(['box.rack', 'items', 'attachments', 'borrowingLogs.handledByUser', 'verifiedByUser'])
            ->where('nomor_pb', $code)
            ->orWhere('nomor_pb', 'like', '%' . $code . '%')
            ->first();

        if ($doc) {
            return $this->formatDocumentResponse($doc, $code);
        }

        // 3. Direct Box Number Match (e.g. Box 01, BOX-01, Box 12)
        $cleanBoxCode = str_replace(['-', '_'], ' ', $code);
        $box = Box::with(['rack', 'documents'])
            ->where('nomor_box', $code)
            ->orWhere('nomor_box', $cleanBoxCode)
            ->orWhere('nomor_box', 'like', '%' . $code . '%')
            ->first();

        if ($box) {
            return $this->formatBoxResponse($box, $code);
        }

        // 4. Numeric ID fallback
        if (is_numeric($code)) {
            $doc = Document::with(['box.rack', 'items', 'attachments', 'borrowingLogs.handledByUser', 'verifiedByUser'])->find($code);
            if ($doc) {
                return $this->formatDocumentResponse($doc, $code);
            }

            $box = Box::with(['rack', 'documents'])->find($code);
            if ($box) {
                return $this->formatBoxResponse($box, $code);
            }
        }

        return response()->json([
            'success' => false,
            'message' => "Data tidak ditemukan untuk barcode/QR '{$code}'. Pastikan kode terdaftar di sistem.",
            'code'    => $code,
        ], 404);
    }

    private function formatDocumentResponse(Document $doc, string $rawCode)
    {
        $activeBorrowing = $doc->borrowingLogs()->where('status_pinjam', 'Dipinjam')->latest()->first();

        $action = 'view_detail';
        $actionLabel = 'Lihat Detail PB';

        if ($doc->status === 'Menunggu Arsip Fisik') {
            $action = 'verify_physical';
            $actionLabel = 'Verifikasi Fisik & Simpan ke Rak';
        } elseif (str_contains($doc->status, 'Dipinjam')) {
            $action = 'return_physical';
            $actionLabel = 'Pengesahan Kembali Berkas PB';
        } elseif ($doc->status === 'Terverifikasi & Diarsipkan') {
            $action = 'borrow_document';
            $actionLabel = 'Pinjamkan Berkas Fisik PB';
        }

        return response()->json([
            'success'          => true,
            'type'             => 'document',
            'code'             => $rawCode,
            'action'           => $action,
            'action_label'     => $actionLabel,
            'data'             => [
                'id'                   => $doc->id,
                'nomor_pb'             => $doc->nomor_pb,
                'tanggal_pb'           => $doc->tanggal_pb?->format('Y-m-d'),
                'tanggal_indonesia'    => $doc->tanggal_indonesia,
                'bagian'               => $doc->bagian,
                'nama_pemohon'         => $doc->nama_pemohon,
                'status'               => $doc->status,
                'lokasi_rak'           => $doc->lokasi_rak_formatted,
                'nomor_box'            => $doc->nomor_box_formatted,
                'total_items'          => $doc->items->count(),
                'total_harga'          => $doc->total_harga,
                'active_borrowing'     => $activeBorrowing ? [
                    'id'                  => $activeBorrowing->id,
                    'nama_peminjam'       => $activeBorrowing->nama_peminjam,
                    'departemen_peminjam' => $activeBorrowing->departemen_peminjam,
                    'tanggal_pinjam'      => $activeBorrowing->tanggal_pinjam?->format('d M Y'),
                    'batas_kembali'       => $activeBorrowing->batas_kembali?->format('d M Y'),
                    'is_overdue'          => $activeBorrowing->is_overdue,
                    'sisa_hari'           => $activeBorrowing->sisa_hari,
                ] : null,
            ],
            'message'          => "Dokumen PB {$doc->nomor_pb} berhasil dipindai.",
        ]);
    }

    private function formatBoxResponse(Box $box, string $rawCode)
    {
        return response()->json([
            'success'      => true,
            'type'         => 'box',
            'code'         => $rawCode,
            'action'       => 'inspect_box',
            'action_label' => 'Inspeksi Ordner Dossier',
            'data'         => [
                'id'                   => $box->id,
                'nomor_box'            => $box->nomor_box,
                'rack_id'              => $box->rack_id,
                'rack_kode'            => $box->rack?->kode_rak,
                'rack_nama'            => $box->rack?->nama_rak,
                'zona_lokasi'          => $box->rack?->zona_lokasi,
                'baris_ke'             => $box->baris_ke,
                'kolom_ke'             => $box->kolom_ke,
                'total_dokumen'        => $box->total_dokumen,
                'kapasitas_maksimal'   => $box->kapasitas_maksimal,
                'persentase_kapasitas' => $box->persentase_kapasitas,
                'sisa_kapasitas'       => $box->sisa_kapasitas,
                'status'               => $box->status,
                'has_borrowed_docs'    => $box->has_borrowed_docs,
            ],
            'message'      => "Box {$box->nomor_box} pada {$box->rack?->kode_rak} berhasil dipindai.",
        ]);
    }
}
