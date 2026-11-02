<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Document;
use App\Models\Rack;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $stats = [
            'total'        => Document::count(),
            'verified'     => Document::where('status', 'like', '%Terverifikasi%')->orWhere('status', 'like', '%Selesai%')->count(),
            'pending'      => Document::where('status', 'like', '%Menunggu%')->count(),
            'borrowed'     => Document::where('status', 'like', '%Dipinjam%')->count(),
            'overdue'      => BorrowingLog::where('status_pinjam', 'Dipinjam')->where('batas_kembali', '<', $today)->count(),
        ];

        // Active overdue borrowings
        $overdueBorrowings = BorrowingLog::with(['document.box.rack'])
            ->where('status_pinjam', 'Dipinjam')
            ->where('batas_kembali', '<', $today)
            ->latest('batas_kembali')
            ->take(5)
            ->get();

        // Recent documents
        $recentDocs = Document::with(['items', 'box.rack'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($doc) {
                return [
                    'id'                => $doc->id,
                    'nomor_pb'          => $doc->nomor_pb,
                    'tanggal_indonesia' => $doc->tanggal_indonesia,
                    'bagian'            => $doc->bagian,
                    'nama_pemohon'      => $doc->nama_pemohon,
                    'status'            => $doc->status,
                    'status_class'      => $doc->status_class,
                    'lokasi_rak'        => $doc->lokasi_rak_formatted,
                    'nomor_box'         => $doc->nomor_box_formatted,
                    'total_item'        => $doc->items->count(),
                    'total_harga'       => $doc->total_harga,
                ];
            });

        // Racks capacity summary
        $racks = Rack::with('boxes.documents')->get()->map(function ($rack) {
            return [
                'id'                   => $rack->id,
                'kode_rak'             => $rack->kode_rak,
                'nama_rak'             => $rack->nama_rak,
                'zona_lokasi'          => $rack->zona_lokasi,
                'warna_label'          => $rack->warna_label,
                'total_dokumen'        => $rack->total_dokumen,
                'kapasitas_maksimal'   => $rack->kapasitas_maksimal,
                'persentase_kapasitas' => $rack->persentase_kapasitas,
                'status_kapasitas'     => $rack->status_kapasitas,
                'total_box'            => $rack->boxes->count(),
            ];
        });

        // Recent activity audit trail
        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        return response()->json([
            'stats'             => $stats,
            'overdueBorrowings' => $overdueBorrowings,
            'recentDocs'        => $recentDocs,
            'racks'             => $racks,
            'recentActivities'  => $recentActivities,
        ]);
    }
}
