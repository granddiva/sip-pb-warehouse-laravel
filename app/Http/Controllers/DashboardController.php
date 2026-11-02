<?php

namespace App\Http\Controllers;

use App\Models\Document;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCount        = Document::count();
        $verifiedCount     = Document::where('status', 'like', '%Terverifikasi%')
                              ->orWhere('status', 'like', '%Selesai%')->count();
        $pendingCount      = Document::where('status', 'like', '%Menunggu%')->count();
        $borrowedCount     = Document::where('status', 'like', '%Dipinjam%')->count();

        $recentDocs = Document::with('items')->latest()->take(5)->get();

        $rakList = [
            ['kode' => 'Rak A', 'nama' => 'Rak A - Arsip Machining & Workshop',   'kapasitas' => 85],
            ['kode' => 'Rak B', 'nama' => 'Rak B - Arsip NDT & Inspeksi',          'kapasitas' => 62],
            ['kode' => 'Rak C', 'nama' => 'Rak C - Arsip Logistik & HSE',          'kapasitas' => 74],
            ['kode' => 'Rak D', 'nama' => 'Rak D - Arsip Maintenance & Umum',      'kapasitas' => 45],
        ];

        return view('dashboard.index', compact(
            'totalCount', 'verifiedCount', 'pendingCount', 'borrowedCount',
            'recentDocs', 'rakList'
        ));
    }
}
