<?php

namespace App\Http\Controllers;

use App\Models\Document;

class RackController extends Controller
{
    public function index()
    {
        $rakList = [
            ['kode' => 'Rak A', 'nama' => 'Rak A - Arsip Machining & Workshop',  'kapasitas' => 85, 'warna' => '#0284c7'],
            ['kode' => 'Rak B', 'nama' => 'Rak B - Arsip NDT & Inspeksi',         'kapasitas' => 62, 'warna' => '#059669'],
            ['kode' => 'Rak C', 'nama' => 'Rak C - Arsip Logistik & HSE',         'kapasitas' => 74, 'warna' => '#d97706'],
            ['kode' => 'Rak D', 'nama' => 'Rak D - Arsip Maintenance & Umum',     'kapasitas' => 45, 'warna' => '#7c3aed'],
        ];

        // Ambil dokumen per rak
        $docsByRak = Document::with('items')
            ->orderBy('lokasi_rak')
            ->orderBy('nomor_box')
            ->get()
            ->groupBy('lokasi_rak');

        return view('racks.index', compact('rakList', 'docsByRak'));
    }
}
