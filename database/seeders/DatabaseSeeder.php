<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'name'     => 'Admin Warehouse',
            'role'     => 'Admin Warehouse',
            'nip'      => 'BMS-WH-0012',
            'avatar'   => 'AW',
        ]);

        User::create([
            'username' => 'petugas',
            'password' => Hash::make('petugas123'),
            'name'     => 'Staff Warehouse',
            'role'     => 'Staff Warehouse',
            'nip'      => 'BMS-WH-0045',
            'avatar'   => 'SW',
        ]);

        // Dokumen PB awal
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
                'uploaded_by'   => 'Budi Hendrawan (Admin)',
                'items' => [
                    ['nama' => 'Carbide Insert Sandvik WNMG 080408-PM', 'part_number' => 'SNV-WNMG-0804', 'jumlah' => 20, 'satuan' => 'Pcs', 'harga_satuan' => 145000, 'spesifikasi' => 'Grade 4325 untuk turning casing pipe'],
                    ['nama' => 'Cutting Coolant Semi-Synthetic Soluble', 'part_number' => 'CCL-DRUM-200L', 'jumlah' => 1, 'satuan' => 'Drum', 'harga_satuan' => 3850000, 'spesifikasi' => 'Drum 200 Liter anti-karat'],
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
                'uploaded_by'   => 'Siti Rahmawati (Petugas)',
                'items' => [
                    ['nama' => 'Magnaflux 7HF Black Magnetic Ink Spray', 'part_number' => 'MGX-7HF-400ML', 'jumlah' => 12, 'satuan' => 'Can', 'harga_satuan' => 285000, 'spesifikasi' => 'Kaleng spray 400ml aerosol'],
                    ['nama' => 'Magnaflux WCP-2 White Contrast Paint',   'part_number' => 'MGX-WCP2-400ML', 'jumlah' => 8, 'satuan' => 'Can', 'harga_satuan' => 260000, 'spesifikasi' => 'Fast drying white paint 400ml'],
                    ['nama' => 'UV Blacklight Bulb 100W Replacement',    'part_number' => 'BLB-100W-UV', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 650000, 'spesifikasi' => 'Untuk lampu inspeksi fluorescent NDT'],
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
                'uploaded_by'   => 'Budi Hendrawan (Admin)',
                'items' => [
                    ['nama' => 'Safety Helmet MSA V-Gard White Staz-On',              'part_number' => 'MSA-VG-WHT',   'jumlah' => 15, 'satuan' => 'Unit', 'harga_satuan' => 220000, 'spesifikasi' => 'Standar ANSI Z89.1 Type 1'],
                    ['nama' => 'Full Body Harness Double Lanyard Shock Absorber',      'part_number' => 'KAR-FBH-DL',   'jumlah' => 6,  'satuan' => 'Set',  'harga_satuan' => 890000, 'spesifikasi' => 'Karam PN 56 Double Big Hook'],
                    ['nama' => 'Safety Glasses 3M Virtua Anti-Fog Clear',              'part_number' => '3M-VRT-11228', 'jumlah' => 30, 'satuan' => 'Pcs',  'harga_satuan' => 45000,  'spesifikasi' => 'Lensa bening perlindungan UV'],
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
                'uploaded_by'   => 'Siti Rahmawati (Petugas)',
                'items' => [
                    ['nama' => 'Steel Strapping Band 3/4 inch x 0.031', 'part_number' => 'STB-34-STEEL', 'jumlah' => 3,  'satuan' => 'Roll', 'harga_satuan' => 1350000, 'spesifikasi' => 'Heavy duty roll @ 45 kg'],
                    ['nama' => 'Klem Strapping Seng 3/4 inch',          'part_number' => 'KLM-34-ZINC',  'jumlah' => 5,  'satuan' => 'Pack', 'harga_satuan' => 180000,  'spesifikasi' => 'Isi 500 pcs per pack'],
                    ['nama' => 'Plastic Stretch Film Roll 50cm x 300m', 'part_number' => 'STF-5030-CLR', 'jumlah' => 10, 'satuan' => 'Roll', 'harga_satuan' => 115000,  'spesifikasi' => 'Ketebalan 20 micron bening'],
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
                'catatan_arsip' => 'Dokumen sedang dipinjam oleh Bagian Keuangan untuk audit internal.',
                'uploaded_by'   => 'Budi Hendrawan (Admin)',
                'items' => [
                    ['nama' => 'SKF Spherical Roller Bearing 22216 EK', 'part_number' => 'SKF-22216-EK', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 2450000, 'spesifikasi' => 'Tapered bore include adapter sleeve'],
                    ['nama' => 'Shell Gadus S2 V220 2 Grease EP',       'part_number' => 'SHL-GDS-18KG', 'jumlah' => 1, 'satuan' => 'Pail', 'harga_satuan' => 1650000, 'spesifikasi' => 'Pail 18 Kg gemuk tekanan tinggi'],
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
                'uploaded_by'   => 'Siti Rahmawati (Petugas)',
                'items' => [
                    ['nama' => 'Bestolife 2000 Non-Metallic Thread Compound',     'part_number' => 'BST-2000-5GAL', 'jumlah' => 2,  'satuan' => 'Pail',   'harga_satuan' => 3400000, 'spesifikasi' => 'Pail 5 Gallon untuk casing & tubing'],
                    ['nama' => 'Thread Protector Composite 9-5/8 inch Pin & Box', 'part_number' => 'PRT-958-COMP',  'jumlah' => 50, 'satuan' => 'Pasang', 'harga_satuan' => 85000,   'spesifikasi' => 'Heavy duty steel-composite casing protector'],
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
                'uploaded_by'   => 'Budi Hendrawan (Admin)',
                'items' => [
                    ['nama' => 'Calibration Test Block IIW Type 1 Carbon Steel', 'part_number' => 'BLK-IIW-V1-CS', 'jumlah' => 1, 'satuan' => 'Set',  'harga_satuan' => 4750000, 'spesifikasi' => 'Standar ISO 2400 include box kayu & sertifikat'],
                    ['nama' => 'Ultrasonic Transducer Probe Dual Element 5MHz',  'part_number' => 'UT-PRB-5MHZ',   'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 2150000, 'spesifikasi' => 'Kabel Lemo 00 to Microdot'],
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
                'uploaded_by'   => 'Budi Hendrawan (Admin)',
                'items' => [
                    ['nama' => 'Ribbon Barcode Wax-Resin 110mm x 300m',   'part_number' => 'RBN-WXR-110',   'jumlah' => 6,  'satuan' => 'Roll', 'harga_satuan' => 145000, 'spesifikasi' => 'Core 1 inch out face'],
                    ['nama' => 'Label Stiker Polyart Tahan Oli 100x50mm', 'part_number' => 'LBL-PLY-10050', 'jumlah' => 10, 'satuan' => 'Roll', 'harga_satuan' => 210000, 'spesifikasi' => 'Isi 1.000 label per roll'],
                ],
            ],
        ];

        foreach ($docs as $docData) {
            $items = $docData['items'];
            unset($docData['items']);
            $doc = Document::create($docData);
            foreach ($items as $item) {
                $doc->items()->create($item);
            }
        }
    }
}
