<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Rack;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index()
    {
        $racks = Rack::with(['boxes.documents.items'])->get()->map(function ($rack) {
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
                        'id'                   => $box->id,
                        'nomor_box'            => $box->nomor_box,
                        'baris_ke'             => $box->baris_ke,
                        'kolom_ke'             => $box->kolom_ke,
                        'kapasitas_maksimal'   => $box->kapasitas_maksimal,
                        'status'               => $box->status,
                        'catatan'              => $box->catatan,
                        'total_dokumen'        => $box->total_dokumen,
                        'persentase_kapasitas' => $box->persentase_kapasitas,
                        'sisa_kapasitas'       => $box->sisa_kapasitas,
                        'is_warning'           => $box->is_warning,
                        'is_full'              => $box->is_full,
                        'has_borrowed_docs'    => $box->has_borrowed_docs,
                        'visual_status_class'  => $box->visual_status_class,
                        'documents'            => $box->documents->map(function ($d) {
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
        $box->load(['rack', 'documents.items', 'documents.attachments']);

        return response()->json([
            'box' => [
                'id'                   => $box->id,
                'nomor_box'            => $box->nomor_box,
                'rack_kode'            => $box->rack?->kode_rak,
                'rack_nama'            => $box->rack?->nama_rak,
                'zona_lokasi'          => $box->rack?->zona_lokasi,
                'total_dokumen'        => $box->total_dokumen,
                'kapasitas_maksimal'   => $box->kapasitas_maksimal,
                'persentase_kapasitas' => $box->persentase_kapasitas,
                'sisa_kapasitas'       => $box->sisa_kapasitas,
                'status'               => $box->status,
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
                    'total_items'       => $doc->items->count(),
                    'total_harga'       => $doc->total_harga,
                ];
            }),
        ]);
    }

    public function toggleLock(Box $box)
    {
        $newStatus = $box->status === 'Penuh (Terkunci)' ? 'Tersedia' : 'Penuh (Terkunci)';
        $box->update(['status' => $newStatus]);

        \App\Models\ActivityLog::log(
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
}
