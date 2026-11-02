<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rack;
use App\Models\Box;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\BorrowingLog;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'name'     => 'Admin Warehouse',
            'role'     => 'Admin Warehouse',
            'nip'      => 'BMS-WH-0012',
            'avatar'   => 'AW',
        ]);

        $petugas = User::create([
            'username' => 'petugas',
            'password' => Hash::make('petugas123'),
            'name'     => 'Staff Warehouse',
            'role'     => 'Staff Warehouse',
            'nip'      => 'BMS-WH-0045',
            'avatar'   => 'SW',
        ]);

        $auditor = User::create([
            'username' => 'auditor',
            'password' => Hash::make('auditor123'),
            'name'     => 'Tim Audit & QC',
            'role'     => 'Auditor',
            'nip'      => 'BMS-AUD-0099',
            'avatar'   => 'AU',
        ]);

        // 2. Master Racks (Lemari Rak Fisik Gudang)
        $racksData = [
            [
                'kode_rak'    => 'Rak A',
                'nama_rak'    => 'Rak A - Arsip Machining & Workshop',
                'zona_lokasi' => 'Zona 1 - Workshop Bay',
                'warna_label' => '#0284c7', // Sky Blue
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan ordner berkas pengadaan workshop lathe CNC, milling, threading, dan perkakas potong logam.',
            ],
            [
                'kode_rak'    => 'Rak B',
                'nama_rak'    => 'Rak B - Arsip NDT & Inspeksi Mutu',
                'zona_lokasi' => 'Zona 2 - Inspection Yard',
                'warna_label' => '#10b981', // Emerald Green
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan ordner berkas Non-Destructive Testing (NDT), ultrasonic gauge, sertifikat kalibrasi QC.',
            ],
            [
                'kode_rak'    => 'Rak C',
                'nama_rak'    => 'Rak C - Arsip Logistik & HSE / K3L',
                'zona_lokasi' => 'Zona 3 - Central Warehouse',
                'warna_label' => '#f59e0b', // Safety Amber
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan berkas pengadaan Alat Pelindung Diri (APD), perlengkapan keselamatan kerja, dan material packing.',
            ],
            [
                'kode_rak'    => 'Rak D',
                'nama_rak'    => 'Rak D - Arsip Maintenance & Operasional',
                'zona_lokasi' => 'Zona 4 - Utility Room',
                'warna_label' => '#6366f1', // Indigo
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan ordner berkas suku cadang alat berat, overhead crane, pelumas industri, dan mesin pabrik.',
            ],
        ];

        $rackMap = [];
        foreach ($racksData as $rData) {
            $rack = Rack::create($rData);
            $rackMap[$rack->kode_rak] = $rack;
        }

        // 3. Master Boxes (Ordner / Box Arsip)
        $boxesConfig = [
            'Rak A' => [
                ['nomor' => 'Box 01', 'baris' => 1, 'kolom' => 1, 'kapasitas' => 20],
                ['nomor' => 'Box 02', 'baris' => 1, 'kolom' => 2, 'kapasitas' => 20],
                ['nomor' => 'Box 03', 'baris' => 1, 'kolom' => 3, 'kapasitas' => 20],
                ['nomor' => 'Box 04', 'baris' => 2, 'kolom' => 1, 'kapasitas' => 20],
                ['nomor' => 'Box 05', 'baris' => 2, 'kolom' => 2, 'kapasitas' => 20],
                ['nomor' => 'Box 12', 'baris' => 4, 'kolom' => 3, 'kapasitas' => 20],
            ],
            'Rak B' => [
                ['nomor' => 'Box 01', 'baris' => 1, 'kolom' => 1, 'kapasitas' => 20],
                ['nomor' => 'Box 04', 'baris' => 2, 'kolom' => 1, 'kapasitas' => 20],
                ['nomor' => 'Box 08', 'baris' => 3, 'kolom' => 2, 'kapasitas' => 20],
            ],
            'Rak C' => [
                ['nomor' => 'Box 01', 'baris' => 1, 'kolom' => 1, 'kapasitas' => 20],
                ['nomor' => 'Box 02', 'baris' => 1, 'kolom' => 2, 'kapasitas' => 20],
                ['nomor' => 'Box 05', 'baris' => 2, 'kolom' => 2, 'kapasitas' => 20],
                ['nomor' => 'Box 07', 'baris' => 3, 'kolom' => 1, 'kapasitas' => 20],
            ],
            'Rak D' => [
                ['nomor' => 'Box 01', 'baris' => 1, 'kolom' => 1, 'kapasitas' => 20],
                ['nomor' => 'Box 09', 'baris' => 3, 'kolom' => 3, 'kapasitas' => 20],
                ['nomor' => 'Box 10', 'baris' => 4, 'kolom' => 1, 'kapasitas' => 20],
            ],
        ];

        $boxMap = []; // Key: "Rak A-Box 03" => Box model
        foreach ($boxesConfig as $rackKode => $boxList) {
            $rack = $rackMap[$rackKode];
            foreach ($boxList as $b) {
                $box = Box::create([
                    'rack_id'            => $rack->id,
                    'nomor_box'          => $b['nomor'],
                    'baris_ke'           => $b['baris'],
                    'kolom_ke'           => $b['kolom'],
                    'kapasitas_maksimal' => $b['kapasitas'],
                    'status'             => 'Tersedia',
                    'catatan'            => "Ordner fisik di {$rack->nama_rak} posisi baris {$b['baris']} kolom {$b['kolom']}",
                ]);
                $boxMap["{$rackKode}-{$b['nomor']}"] = $box;
            }
        }

        // 4. Dokumen PB Awal
        $docs = [
            [
                'nomor_pb'      => 'PB/BMS-WH/2026/09/001',
                'tanggal_pb'    => '2026-09-21',
                'bagian'        => 'Workshop Machining & Threading',
                'nama_pemohon'  => 'Agus Pratama',
                'nip_pemohon'   => 'BMS-MCH-0104',
                'keterangan'    => 'Pengadaan Carbide Inserts Sandvik Coromant & coolant soluble oil 1 drum untuk mesin bubut CNC lathe.',
                'status'        => 'Terverifikasi & Diarsipkan',
                'lokasi_rak'    => 'Rak A',
                'nomor_box'     => 'Box 03',
                'dokumen'       => 'Scan_PB_BMS_WH_001.pdf',
                'dokumen_size'  => '1.9 MB',
                'catatan_arsip' => 'Dokumen fisik tersimpan rapi dalam ordner biru Box 03 Rak A. Disertai nota tanda terima workshop.',
                'uploaded_by'   => 'Admin Warehouse',
                'user_id'       => $admin->id,
                'items' => [
                    ['nama' => 'Carbide Insert Sandvik WNMG 080408-PM', 'part_number' => 'SNV-WNMG-0804', 'jumlah' => 20, 'satuan' => 'Pcs', 'harga_satuan' => 145000, 'spesifikasi' => 'Grade 4325 untuk turning casing pipe'],
                    ['nama' => 'Cutting Coolant Semi-Synthetic Soluble', 'part_number' => 'CCL-DRUM-200L', 'jumlah' => 1, 'satuan' => 'Drum', 'harga_satuan' => 3850000, 'spesifikasi' => 'Drum 200 Liter anti-karat'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'Scan_PB_BMS_WH_001.pdf', 'file_path' => 'documents/Scan_PB_BMS_WH_001.pdf', 'file_size' => '1.9 MB', 'mime_type' => 'application/pdf'],
                    ['jenis_dokumen' => 'Purchase Order (PO)', 'file_name' => 'PO_Sandvik_Coromant_4402.pdf', 'file_path' => 'documents/PO_Sandvik_Coromant_4402.pdf', 'file_size' => '650 KB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-NDT/2026/09/002',
                'tanggal_pb'    => '2026-09-22',
                'bagian'        => 'NDT & Tubular Inspection',
                'nama_pemohon'  => 'Dedi Suhendar, S.T.',
                'nip_pemohon'   => 'BMS-NDT-0082',
                'keterangan'    => 'Permintaan aerosol magnetic particle inspection Magnaflux 7HF & white contrast paint WCP-2.',
                'status'        => 'Terverifikasi & Diarsipkan',
                'lokasi_rak'    => 'Rak B',
                'nomor_box'     => 'Box 08',
                'dokumen'       => 'Scan_PB_BMS_NDT_002.pdf',
                'dokumen_size'  => '2.4 MB',
                'catatan_arsip' => 'Arsip fisik lengkap tanda tangan Manager Mutu NDT.',
                'uploaded_by'   => 'Staff Warehouse',
                'user_id'       => $petugas->id,
                'items' => [
                    ['nama' => 'Magnaflux 7HF Black Magnetic Ink Spray', 'part_number' => 'MGX-7HF-400ML', 'jumlah' => 12, 'satuan' => 'Can', 'harga_satuan' => 285000, 'spesifikasi' => 'Kaleng spray 400ml aerosol'],
                    ['nama' => 'Magnaflux WCP-2 White Contrast Paint',   'part_number' => 'MGX-WCP2-400ML', 'jumlah' => 8, 'satuan' => 'Can', 'harga_satuan' => 260000, 'spesifikasi' => 'Fast drying white paint 400ml'],
                    ['nama' => 'UV Blacklight Bulb 100W Replacement',    'part_number' => 'BLB-100W-UV', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 650000, 'spesifikasi' => 'Untuk lampu inspeksi fluorescent NDT'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'Scan_PB_BMS_NDT_002.pdf', 'file_path' => 'documents/Scan_PB_BMS_NDT_002.pdf', 'file_size' => '2.4 MB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-HSE/2026/09/003',
                'tanggal_pb'    => '2026-09-23',
                'bagian'        => 'HSE & K3L',
                'nama_pemohon'  => 'Fajar Nugroho, S.K.M.',
                'nip_pemohon'   => 'BMS-HSE-0056',
                'keterangan'    => 'Pengadaan APD pengganti: safety helmet V-Gard MSA, full body harness double lanyard, safety glasses.',
                'status'        => 'Menunggu Arsip Fisik',
                'lokasi_rak'    => 'Rak C',
                'nomor_box'     => 'Box 02',
                'dokumen'       => 'PB_HSE_K3L_PengadaanAPD.pdf',
                'dokumen_size'  => '1.1 MB',
                'catatan_arsip' => 'Dokumen digital telah diunggah. Menunggu berkas fisik asli dari departemen HSE.',
                'uploaded_by'   => 'Admin Warehouse',
                'user_id'       => $admin->id,
                'items' => [
                    ['nama' => 'Safety Helmet MSA V-Gard White Staz-On',              'part_number' => 'MSA-VG-WHT',   'jumlah' => 15, 'satuan' => 'Unit', 'harga_satuan' => 220000, 'spesifikasi' => 'Standar ANSI Z89.1 Type 1'],
                    ['nama' => 'Full Body Harness Double Lanyard Shock Absorber',      'part_number' => 'KAR-FBH-DL',   'jumlah' => 6,  'satuan' => 'Set',  'harga_satuan' => 890000, 'spesifikasi' => 'Karam PN 56 Double Big Hook'],
                    ['nama' => 'Safety Glasses 3M Virtua Anti-Fog Clear',              'part_number' => '3M-VRT-11228', 'jumlah' => 30, 'satuan' => 'Pcs',  'harga_satuan' => 45000,  'spesifikasi' => 'Lensa bening perlindungan UV'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'PB_HSE_K3L_PengadaanAPD.pdf', 'file_path' => 'documents/PB_HSE_K3L_PengadaanAPD.pdf', 'file_size' => '1.1 MB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-LOG/2026/09/004',
                'tanggal_pb'    => '2026-09-24',
                'bagian'        => 'Warehouse & Logistik',
                'nama_pemohon'  => 'Rudi Hartono',
                'nip_pemohon'   => 'BMS-LOG-0031',
                'keterangan'    => 'Pengadaan strapping band baja 3/4 inch, klem seng, stretch film heavy duty untuk packing pengiriman material.',
                'status'        => 'Terverifikasi & Diarsipkan',
                'lokasi_rak'    => 'Rak C',
                'nomor_box'     => 'Box 05',
                'dokumen'       => 'Scan_PB_Logistik_Packing_0924.pdf',
                'dokumen_size'  => '2.1 MB',
                'catatan_arsip' => 'Disimpan di Rak C Box 05. Telah diperiksa fisik barang masuk warehouse.',
                'uploaded_by'   => 'Staff Warehouse',
                'user_id'       => $petugas->id,
                'items' => [
                    ['nama' => 'Steel Strapping Band 3/4 inch x 0.031', 'part_number' => 'STB-34-STEEL', 'jumlah' => 3,  'satuan' => 'Roll', 'harga_satuan' => 1350000, 'spesifikasi' => 'Heavy duty roll @ 45 kg'],
                    ['nama' => 'Klem Strapping Seng 3/4 inch',          'part_number' => 'KLM-34-ZINC',  'jumlah' => 5,  'satuan' => 'Pack', 'harga_satuan' => 180000,  'spesifikasi' => 'Isi 500 pcs per pack'],
                    ['nama' => 'Plastic Stretch Film Roll 50cm x 300m', 'part_number' => 'STF-5030-CLR', 'jumlah' => 10, 'satuan' => 'Roll', 'harga_satuan' => 115000,  'spesifikasi' => 'Ketebalan 20 micron bening'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'Scan_PB_Logistik_Packing_0924.pdf', 'file_path' => 'documents/Scan_PB_Logistik_Packing_0924.pdf', 'file_size' => '2.1 MB', 'mime_type' => 'application/pdf'],
                    ['jenis_dokumen' => 'Surat Jalan / Delivery Order (DO)', 'file_name' => 'DO_Packing_Band_Steel.pdf', 'file_path' => 'documents/DO_Packing_Band_Steel.pdf', 'file_size' => '820 KB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-MTN/2026/09/005',
                'tanggal_pb'    => '2026-09-25',
                'bagian'        => 'Maintenance & Utility',
                'nama_pemohon'  => 'Imran Rosyadi',
                'nip_pemohon'   => 'BMS-MTN-0074',
                'keterangan'    => 'Penggantian bearing SKF spherical roller & grease Shell Gadus S2 V220 untuk overhead crane bay 2.',
                'status'        => 'Dokumen Dipinjam',
                'lokasi_rak'    => 'Rak D',
                'nomor_box'     => 'Box 09',
                'dokumen'       => 'PB_Maintenance_Crane_Bay2.pdf',
                'dokumen_size'  => '3.2 MB',
                'catatan_arsip' => 'Dokumen sedang dipinjam oleh Bagian Keuangan/Audit internal.',
                'uploaded_by'   => 'Admin Warehouse',
                'user_id'       => $admin->id,
                'items' => [
                    ['nama' => 'SKF Spherical Roller Bearing 22216 EK', 'part_number' => 'SKF-22216-EK', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 2450000, 'spesifikasi' => 'Tapered bore include adapter sleeve'],
                    ['nama' => 'Shell Gadus S2 V220 2 Grease EP',       'part_number' => 'SHL-GDS-18KG', 'jumlah' => 1, 'satuan' => 'Pail', 'harga_satuan' => 1650000, 'spesifikasi' => 'Pail 18 Kg gemuk tekanan tinggi'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'PB_Maintenance_Crane_Bay2.pdf', 'file_path' => 'documents/PB_Maintenance_Crane_Bay2.pdf', 'file_size' => '3.2 MB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-OPS/2026/09/006',
                'tanggal_pb'    => '2026-09-26',
                'bagian'        => 'Operasional Lapangan',
                'nama_pemohon'  => 'Bambang Triyono',
                'nip_pemohon'   => 'BMS-OPS-0023',
                'keterangan'    => 'Permintaan thread compound Bestolife 2000 & thread protector pipa casing 9-5/8 inch 50 pasang.',
                'status'        => 'Terverifikasi & Diarsipkan',
                'lokasi_rak'    => 'Rak A',
                'nomor_box'     => 'Box 12',
                'dokumen'       => 'Scan_PB_BMS_OPS_DopeThread.pdf',
                'dokumen_size'  => '1.7 MB',
                'catatan_arsip' => 'Tersimpan di Rak A Box 12. Validasi lengkap dengan signature client PT Pertamina Hulu Rokan.',
                'uploaded_by'   => 'Staff Warehouse',
                'user_id'       => $petugas->id,
                'items' => [
                    ['nama' => 'Bestolife 2000 Non-Metallic Thread Compound',     'part_number' => 'BST-2000-5GAL', 'jumlah' => 2,  'satuan' => 'Pail',   'harga_satuan' => 3400000, 'spesifikasi' => 'Pail 5 Gallon untuk casing & tubing'],
                    ['nama' => 'Thread Protector Composite 9-5/8 inch Pin & Box', 'part_number' => 'PRT-958-COMP',  'jumlah' => 50, 'satuan' => 'Pasang', 'harga_satuan' => 85000,   'spesifikasi' => 'Heavy duty steel-composite casing protector'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'Scan_PB_BMS_OPS_DopeThread.pdf', 'file_path' => 'documents/Scan_PB_BMS_OPS_DopeThread.pdf', 'file_size' => '1.7 MB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-QA/2026/09/007',
                'tanggal_pb'    => '2026-09-27',
                'bagian'        => 'Quality Assurance / QC',
                'nama_pemohon'  => 'Maya Indriyani, S.T.',
                'nip_pemohon'   => 'BMS-QA-0019',
                'keterangan'    => 'Pengadaan calibration standard block IIW Type 1 & digital ultrasonic thickness gauge probe dual element 5MHz.',
                'status'        => 'Selesai Pengadaan',
                'lokasi_rak'    => 'Rak B',
                'nomor_box'     => 'Box 04',
                'dokumen'       => 'Scan_PB_QA_Calibration_Block.pdf',
                'dokumen_size'  => '2.8 MB',
                'catatan_arsip' => 'Telah diarsipkan di Rak B Box 04. Sertifikat kalibrasi pabrik dilampirkan.',
                'uploaded_by'   => 'Admin Warehouse',
                'user_id'       => $admin->id,
                'items' => [
                    ['nama' => 'Calibration Test Block IIW Type 1 Carbon Steel', 'part_number' => 'BLK-IIW-V1-CS', 'jumlah' => 1, 'satuan' => 'Set',  'harga_satuan' => 4750000, 'spesifikasi' => 'Standar ISO 2400 include box kayu & sertifikat'],
                    ['nama' => 'Ultrasonic Transducer Probe Dual Element 5MHz',  'part_number' => 'UT-PRB-5MHZ',   'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 2150000, 'spesifikasi' => 'Kabel Lemo 00 to Microdot'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'Scan_PB_QA_Calibration_Block.pdf', 'file_path' => 'documents/Scan_PB_QA_Calibration_Block.pdf', 'file_size' => '2.8 MB', 'mime_type' => 'application/pdf'],
                    ['jenis_dokumen' => 'Sertifikat Kalibrasi / Inspeksi QC', 'file_name' => 'Cert_Calibration_IIW_Block.pdf', 'file_path' => 'documents/Cert_Calibration_IIW_Block.pdf', 'file_size' => '1.2 MB', 'mime_type' => 'application/pdf'],
                ],
            ],
            [
                'nomor_pb'      => 'PB/BMS-WH/2026/09/008',
                'tanggal_pb'    => '2026-09-28',
                'bagian'        => 'Warehouse & Logistik',
                'nama_pemohon'  => 'Wahyu Setiadi',
                'nip_pemohon'   => 'BMS-LOG-0062',
                'keterangan'    => 'Pengadaan barcode ribbon wax-resin 110mm x 300m dan label thermal transfer tahan oli untuk penomoran racking gudang baru.',
                'status'        => 'Menunggu Arsip Fisik',
                'lokasi_rak'    => 'Rak C',
                'nomor_box'     => 'Box 07',
                'dokumen'       => 'Form_PB_Barcode_Ribbon_Gudang.pdf',
                'dokumen_size'  => '890 KB',
                'catatan_arsip' => 'Input tanggal hari ini. Dokumen menunggu verifikasi fisik dari pengawas gudang.',
                'uploaded_by'   => 'Admin Warehouse',
                'user_id'       => $admin->id,
                'items' => [
                    ['nama' => 'Ribbon Barcode Wax-Resin 110mm x 300m',   'part_number' => 'RBN-WXR-110',   'jumlah' => 6,  'satuan' => 'Roll', 'harga_satuan' => 145000, 'spesifikasi' => 'Core 1 inch out face'],
                    ['nama' => 'Label Stiker Polyart Tahan Oli 100x50mm', 'part_number' => 'LBL-PLY-10050', 'jumlah' => 10, 'satuan' => 'Roll', 'harga_satuan' => 210000, 'spesifikasi' => 'Isi 1.000 label per roll'],
                ],
                'attachments' => [
                    ['jenis_dokumen' => 'Form PB Scan Asli', 'file_name' => 'Form_PB_Barcode_Ribbon_Gudang.pdf', 'file_path' => 'documents/Form_PB_Barcode_Ribbon_Gudang.pdf', 'file_size' => '890 KB', 'mime_type' => 'application/pdf'],
                ],
            ],
        ];

        $createdDocuments = [];
        foreach ($docs as $docData) {
            $items = $docData['items'];
            $attachments = $docData['attachments'] ?? [];
            unset($docData['items'], $docData['attachments']);

            // Tautkan ke box_id riil
            $boxKey = "{$docData['lokasi_rak']}-{$docData['nomor_box']}";
            if (isset($boxMap[$boxKey])) {
                $docData['box_id'] = $boxMap[$boxKey]->id;
            }

            $doc = Document::create($docData);
            $createdDocuments[$doc->nomor_pb] = $doc;

            foreach ($items as $item) {
                $doc->items()->create($item);
            }

            foreach ($attachments as $att) {
                $doc->attachments()->create($att);
            }
        }

        // 5. Sirkulasi Peminjaman (Borrowing Logs)
        // a. Dokumen 5 (PB/BMS-MTN/2026/09/005) - Sedang Dipinjam & OVERDUE (Jatuh tempo 2 Okt 2026)
        if (isset($createdDocuments['PB/BMS-MTN/2026/09/005'])) {
            $doc5 = $createdDocuments['PB/BMS-MTN/2026/09/005'];
            BorrowingLog::create([
                'document_id'         => $doc5->id,
                'handled_by'          => $admin->id,
                'nama_peminjam'       => 'Imran Rosyadi',
                'departemen_peminjam' => 'Maintenance & Utility',
                'keperluan'           => 'Pemeriksaan audit internal dan pencocokan invoice vendor bearing overhead crane',
                'tanggal_pinjam'      => '2026-09-25',
                'batas_kembali'       => '2026-10-02', // Overdue
                'tanggal_kembali'     => null,
                'status_pinjam'       => 'Dipinjam',
                'catatan'             => 'Berkas fisik asli dibawa ke ruang Keuangan & Audit Lantai 2.',
            ]);
        }

        // b. Dokumen 1 (PB/BMS-WH/2026/09/001) - Riwayat Peminjaman yang sudah Dikembalikan
        if (isset($createdDocuments['PB/BMS-WH/2026/09/001'])) {
            $doc1 = $createdDocuments['PB/BMS-WH/2026/09/001'];
            BorrowingLog::create([
                'document_id'         => $doc1->id,
                'handled_by'          => $admin->id,
                'nama_peminjam'       => 'Agus Pratama',
                'departemen_peminjam' => 'Workshop Machining & Threading',
                'keperluan'           => 'Pencocokan part number Sandvik insert untuk re-order',
                'tanggal_pinjam'      => '2026-09-22',
                'batas_kembali'       => '2026-09-24',
                'tanggal_kembali'     => '2026-09-24',
                'status_pinjam'       => 'Dikembalikan',
                'catatan'             => 'Dokumen telah dikembalikan dalam kondisi bersih dan lengkap ke Rak A Box 03.',
            ]);
        }

        // 6. Audit Trail Activity Logs
        ActivityLog::create([
            'user_id'       => $admin->id,
            'activity_type' => 'system_init',
            'description'   => 'Inisialisasi sistem SIP-PB WH v2.0 Enterprise Physical & Digital Archive Management',
            'ip_address'    => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id'       => $admin->id,
            'activity_type' => 'borrow_document',
            'description'   => 'Memproses peminjaman dokumen fisik PB/BMS-MTN/2026/09/005 oleh Imran Rosyadi (Maintenance)',
            'ip_address'    => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id'       => $petugas->id,
            'activity_type' => 'archive_document',
            'description'   => 'Verifikasi dan penempatan fisik berkas PB/BMS-LOG/2026/09/004 ke Rak C Box 05',
            'ip_address'    => '127.0.0.1',
        ]);
    }
}
