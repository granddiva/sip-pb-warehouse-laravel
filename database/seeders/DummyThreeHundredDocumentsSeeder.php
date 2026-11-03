<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rack;
use App\Models\Box;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentAttachment;
use App\Models\BorrowingLog;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DummyThreeHundredDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Dapatkan atau buat pengguna admin, petugas, dan auditor
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => bcrypt('admin123'),
                'name'     => 'Admin Warehouse',
                'role'     => 'Admin Warehouse',
                'nip'      => 'BMS-WH-0012',
                'avatar'   => 'AW',
            ]
        );

        $petugas = User::firstOrCreate(
            ['username' => 'petugas'],
            [
                'password' => bcrypt('petugas123'),
                'name'     => 'Staff Warehouse',
                'role'     => 'Staff Warehouse',
                'nip'      => 'BMS-WH-0045',
                'avatar'   => 'SW',
            ]
        );

        $auditor = User::firstOrCreate(
            ['username' => 'auditor'],
            [
                'password' => bcrypt('auditor123'),
                'name'     => 'Tim Audit & QC',
                'role'     => 'Auditor',
                'nip'      => 'BMS-AUD-0099',
                'avatar'   => 'AU',
            ]
        );

        // 2. Pastikan 4 Rak Utama Tersedia
        $racksData = [
            'Rak A' => [
                'nama_rak'    => 'Rak A - Arsip Machining & Workshop',
                'zona_lokasi' => 'Zona 1 - Workshop Bay',
                'warna_label' => '#0284c7',
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan ordner berkas pengadaan workshop lathe CNC, milling, threading, dan perkakas potong logam.',
            ],
            'Rak B' => [
                'nama_rak'    => 'Rak B - Arsip NDT & Inspeksi Mutu',
                'zona_lokasi' => 'Zona 2 - Inspection Yard',
                'warna_label' => '#10b981',
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan ordner berkas Non-Destructive Testing (NDT), ultrasonic gauge, sertifikat kalibrasi QC.',
            ],
            'Rak C' => [
                'nama_rak'    => 'Rak C - Arsip Logistik & HSE / K3L',
                'zona_lokasi' => 'Zona 3 - Central Warehouse',
                'warna_label' => '#f59e0b',
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan berkas pengadaan Alat Pelindung Diri (APD), perlengkapan keselamatan kerja, dan material packing.',
            ],
            'Rak D' => [
                'nama_rak'    => 'Rak D - Arsip Maintenance & Operasional',
                'zona_lokasi' => 'Zona 4 - Utility Room',
                'warna_label' => '#6366f1',
                'baris'       => 4,
                'kolom'       => 3,
                'keterangan'  => 'Penyimpanan ordner berkas suku cadang alat berat, overhead crane, pelumas industri, dan mesin pabrik.',
            ],
        ];

        $rackModels = [];
        foreach ($racksData as $kode => $rData) {
            $rackModels[$kode] = Rack::firstOrCreate(
                ['kode_rak' => $kode],
                $rData
            );
        }

        // 3. Pastikan Semua 48 Box (12 box per rak: baris 1-4, kolom 1-3) Lengkap
        $boxMap = []; // Key: "Rak A-Box 01" => Box Model
        foreach ($rackModels as $rackKode => $rack) {
            for ($baris = 1; $baris <= 4; $baris++) {
                for ($kolom = 1; $kolom <= 3; $kolom++) {
                    $boxNum = sprintf('Box %02d', ($baris - 1) * 3 + $kolom);
                    $box = Box::firstOrCreate(
                        ['rack_id' => $rack->id, 'nomor_box' => $boxNum],
                        [
                            'baris_ke'           => $baris,
                            'kolom_ke'           => $kolom,
                            'kapasitas_maksimal' => 20,
                            'status'             => 'Tersedia',
                            'catatan'            => "Ordner fisik di {$rack->nama_rak} posisi baris {$baris} kolom {$kolom}",
                        ]
                    );
                    $boxMap["{$rackKode}-{$boxNum}"] = $box;
                }
            }
        }

        // 4. Bersihkan dummy data generasi sebelumnya (id > 10) untuk regenerasi bersih & presisi
        Document::where('id', '>', 10)->delete();

        // 5. Data Katalog 300 Dokumen PT Besmindo Materi Sewatama
        $catalog = $this->getCatalogData();

        $departmentsList = [
            'BMS-MCH' => ['name' => 'Workshop Machining & Threading', 'rack' => 'Rak A', 'count' => 55],
            'BMS-NDT' => ['name' => 'NDT & Tubular Inspection',      'rack' => 'Rak B', 'count' => 45],
            'BMS-WH'  => ['name' => 'Warehouse & Logistik Yard',     'rack' => 'Rak C', 'count' => 50],
            'BMS-HSE' => ['name' => 'HSE & K3L',                     'rack' => 'Rak C', 'count' => 40],
            'BMS-MTN' => ['name' => 'Maintenance & Utility',          'rack' => 'Rak D', 'count' => 45],
            'BMS-OPS' => ['name' => 'Operasional Lapangan',          'rack' => 'Rak D', 'count' => 40],
            'BMS-QC'  => ['name' => 'Quality Assurance / QC',        'rack' => 'Rak B', 'count' => 25],
        ];

        // Siklus 20 status untuk distribusi yang seimbang dan alami:
        // 12 Terverifikasi & Diarsipkan (60%)
        // 3 Menunggu Arsip Fisik (15%)
        // 2 Perlu Revisi Fisik (10%)
        // 2 Dokumen Dipinjam (10%)
        // 1 Selesai Pengadaan (5%)
        $statusesCycle = [
            'Terverifikasi & Diarsipkan', // 0
            'Menunggu Arsip Fisik',      // 1
            'Terverifikasi & Diarsipkan', // 2
            'Dokumen Dipinjam',          // 3
            'Terverifikasi & Diarsipkan', // 4
            'Perlu Revisi Fisik',        // 5
            'Terverifikasi & Diarsipkan', // 6
            'Selesai Pengadaan',         // 7
            'Terverifikasi & Diarsipkan', // 8
            'Menunggu Arsip Fisik',      // 9
            'Terverifikasi & Diarsipkan', // 10
            'Terverifikasi & Diarsipkan', // 11
            'Perlu Revisi Fisik',        // 12
            'Terverifikasi & Diarsipkan', // 13
            'Menunggu Arsip Fisik',      // 14
            'Dokumen Dipinjam',          // 15
            'Terverifikasi & Diarsipkan', // 16
            'Terverifikasi & Diarsipkan', // 17
            'Selesai Pengadaan',         // 18
            'Terverifikasi & Diarsipkan', // 19
        ];

        $rejectionReasons = [
            'Tanda tangan Field Superintendent dan cap basah belum lengkap di formulir PB asli.',
            'Lampiran Purchase Order (PO) belum disertakan dari procurement, hanya print email internal.',
            'Surat Jalan / Delivery Order (DO) fisik belum dilampirkan oleh kurir / ekspedisi lapangan.',
            'Sertifikat Mill Test Certificate (MTC) pabrikan belum dilampirkan untuk batch baja ini.',
            'Fisik part number pada bearing SKF berbeda dengan yang tertulis di formulir PB (tertulis 22216 EK, fisik 22216 CC).',
            'Sertifikat kalibrasi master block sudah expired (kadaluarsa), perlu lembar sertifikat kalibrasi terbaru.',
            'Fisik barang yang diterima kuantitasnya kurang 4 pcs dari yang tertera di form PB dan DO.',
            'Cap legalisir Quality Control (QC) belum distempel pada lembar Berita Acara Serah Terima (BAST).',
        ];

        $borrowersPool = [
            ['name' => 'Hendra Gunawan',    'dept' => 'Workshop Machining & Threading', 'reason' => 'Pemeriksaan part number insert untuk repeat order projek PHE'],
            ['name' => 'Dian Permata, S.T.', 'dept' => 'Quality Assurance / QC',         'reason' => 'Verifikasi sertifikat kalibrasi master test block audit ISO 9001'],
            ['name' => 'Suryadi Pratama',   'dept' => 'Finance & Accounting',           'reason' => 'Pencocokan faktur pajak dan invoice vendor tooling CNC'],
            ['name' => 'Eko Prasetyo',      'dept' => 'Operasional Lapangan',           'reason' => 'Konfirmasi spesifikasi thread compound rig workover Rokan'],
            ['name' => 'Irfan Fauzi',       'dept' => 'Maintenance & Utility',          'reason' => 'Rekonsiliasi kartu suku cadang overhead crane Bay 2'],
            ['name' => 'Nanda Safitri',     'dept' => 'HSE & K3L',                      'reason' => 'Pemeriksaan kelengkapan sertifikasi gas detector BW Honeywell'],
            ['name' => 'Bambang Triyono',   'dept' => 'Operasional Lapangan',           'reason' => 'Pengecekan BAST kiriman rotary slip ke rig yard Duri'],
            ['name' => 'Wahyudi Nugraha',   'dept' => 'Internal Audit',                 'reason' => 'Audit kepatuhan pengarsipan berkas pengadaan kuartal berjalan'],
        ];

        $docSequenceGlobal = 10; // Mulai dari 011 ke atas agar tidak bentrok dengan initial seed
        $borrowedCounter = 0;

        DB::beginTransaction();

        try {
            foreach ($departmentsList as $deptCode => $deptMeta) {
                $deptName = $deptMeta['name'];
                $deptRack = $deptMeta['rack'];
                $targetCount = $deptMeta['count'];
                $itemsCatalog = $catalog[$deptCode] ?? [];

                for ($i = 0; $i < $targetCount; $i++) {
                    $docSequenceGlobal++;
                    $year = ($i % 3 === 0) ? '2025' : '2026';
                    $monthNum = ($i % 12) + 1;
                    $monthStr = sprintf('%02d', $monthNum);
                    $seqStr = sprintf('%03d', $docSequenceGlobal);
                    $nomorPb = "PB/{$deptCode}/{$year}/{$monthStr}/{$seqStr}";

                    $catalogIndex = $i % count($itemsCatalog);
                    $dataEntry = $itemsCatalog[$catalogIndex];

                    // Tentukan status dengan siklus terdistribusi
                    $status = $statusesCycle[$i % count($statusesCycle)];

                    // Tanggal PB (rentang 2025 s/d akhir 2026)
                    $day = min(28, ($i * 3 % 27) + 1);
                    $tanggalPb = Carbon::createFromDate((int)$year, $monthNum, $day);

                    // Alokasi Box yang realistis:
                    // Untuk BMS-MCH: Rak A Box 01 diisi hingga pas 20 dokumen (100% kapasitas - Penuh Terkunci)
                    // Untuk BMS-NDT: Rak B Box 01 diisi hingga 18 dokumen (90% kapasitas - Warning Hampir Penuh)
                    if ($deptCode === 'BMS-MCH' && ($boxMap['Rak A-Box 01']?->documents()->count() ?? 0) < 20) {
                        $boxNumStr = 'Box 01';
                    } elseif ($deptCode === 'BMS-NDT' && ($boxMap['Rak B-Box 01']?->documents()->count() ?? 0) < 18) {
                        $boxNumStr = 'Box 01';
                    } else {
                        // Sebar di Box 02 sampai Box 12
                        $boxNumInt = (($i % 11) + 2);
                        $boxNumStr = sprintf('Box %02d', $boxNumInt);
                    }

                    $boxKey = "{$deptRack}-{$boxNumStr}";
                    $boxModel = $boxMap[$boxKey] ?? null;

                    // Buat Dokumen
                    $doc = new Document();
                    $doc->nomor_pb      = $nomorPb;
                    $doc->tanggal_pb    = $tanggalPb->format('Y-m-d');
                    $doc->bagian        = $deptName;
                    $doc->nama_pemohon  = $dataEntry['applicant_name'];
                    $doc->nip_pemohon   = $dataEntry['applicant_nip'];
                    $doc->keterangan    = $dataEntry['description'];
                    $doc->status        = $status;
                    $doc->lokasi_rak    = $deptRack;
                    $doc->nomor_box     = $boxNumStr;
                    $doc->box_id        = $boxModel?->id;
                    $doc->dokumen       = "Scan_{$deptCode}_{$year}_{$seqStr}.pdf";
                    $doc->dokumen_size  = sprintf('%.1f MB', 1.2 + (($i % 25) * 0.1));
                    $doc->uploaded_by   = ($i % 2 === 0) ? 'Admin Warehouse' : 'Staff Warehouse';
                    $doc->user_id       = ($i % 2 === 0) ? $admin->id : $petugas->id;

                    // Atribut spesifik berdasarkan status
                    if ($status === 'Terverifikasi & Diarsipkan' || $status === 'Selesai Pengadaan') {
                        $doc->verified_at           = $tanggalPb->copy()->addDays(2);
                        $doc->verified_by_user_id   = $admin->id;
                        $doc->kondisi_berkas_fisik  = 'Asli & Cap Basah Lengkap';
                        $doc->catatan_verifikasi    = 'Dokumen fisik diverifikasi lengkap dengan stempel basah dan nota penerimaan gudang.';
                        $doc->catatan_arsip         = "Berkas fisik tersimpan rapi dalam ordner {$boxNumStr} {$deptRack}.";
                    } elseif ($status === 'Menunggu Arsip Fisik') {
                        $doc->catatan_arsip         = 'Formulir PB digital telah diinput. Menunggu pengiriman berkas fisik asli dari departemen pemohon.';
                    } elseif ($status === 'Perlu Revisi Fisik') {
                        $rejectionReason            = $rejectionReasons[$i % count($rejectionReasons)];
                        $doc->catatan_verifikasi    = $rejectionReason;
                        $doc->catatan_arsip         = "Dokumen dikembalikan ke pemohon untuk revisi: {$rejectionReason}";
                    } elseif ($status === 'Dokumen Dipinjam') {
                        $doc->verified_at           = $tanggalPb->copy()->addDays(1);
                        $doc->verified_by_user_id   = $admin->id;
                        $doc->kondisi_berkas_fisik  = 'Asli & Cap Basah Lengkap';
                        $doc->catatan_verifikasi    = 'Telah diverifikasi fisik sebelum dipinjamkan ke departemen terkait.';
                        $doc->catatan_arsip         = "Dokumen fisik sedang berada di luar ordner {$boxNumStr} karena proses peminjaman aktif.";
                    }

                    $doc->save();

                    // Tambahkan Item Dokumen
                    foreach ($dataEntry['items'] as $itemData) {
                        DocumentItem::create([
                            'document_id'  => $doc->id,
                            'nama'         => $itemData['nama'],
                            'part_number'  => $itemData['part_number'],
                            'jumlah'       => $itemData['jumlah'],
                            'satuan'       => $itemData['satuan'],
                            'harga_satuan' => $itemData['harga_satuan'],
                            'spesifikasi'  => $itemData['spesifikasi'],
                        ]);
                    }

                    // Tambahkan Lampiran Dokumen
                    $attachments = $dataEntry['attachments'] ?? [
                        ['jenis' => 'Form PB Scan Asli', 'name' => "Scan_{$deptCode}_{$year}_{$seqStr}.pdf", 'size' => '1.5 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => "PO_{$deptCode}_{$year}_{$seqStr}.pdf", 'size' => '720 KB'],
                        ['jenis' => 'Surat Jalan / Delivery Order (DO)', 'name' => "DO_{$deptCode}_{$year}_{$seqStr}.pdf", 'size' => '510 KB'],
                    ];

                    foreach ($attachments as $att) {
                        DocumentAttachment::create([
                            'document_id'   => $doc->id,
                            'jenis_dokumen' => $att['jenis'],
                            'file_name'     => $att['name'],
                            'file_path'     => "documents/{$att['name']}",
                            'file_size'     => $att['size'],
                            'mime_type'     => 'application/pdf',
                        ]);
                    }

                    // Jika Dokumen Dipinjam, Buat BorrowingLog Aktif
                    if ($status === 'Dokumen Dipinjam') {
                        $borrowedCounter++;
                        $borrower = $borrowersPool[$borrowedCounter % count($borrowersPool)];
                        $isOverdue = ($borrowedCounter % 3 === 0); // 1 dari 3 pinjaman berstatus terlambat (overdue)
                        $tglPinjam = Carbon::now()->subDays($isOverdue ? 18 : 3);
                        $batasKembali = Carbon::now()->subDays($isOverdue ? 5 : -5);

                        BorrowingLog::create([
                            'document_id'         => $doc->id,
                            'handled_by'          => $admin->id,
                            'nama_peminjam'       => $borrower['name'],
                            'departemen_peminjam' => $borrower['dept'],
                            'keperluan'           => $borrower['reason'],
                            'tanggal_pinjam'      => $tglPinjam->format('Y-m-d'),
                            'batas_kembali'       => $batasKembali->format('Y-m-d'),
                            'tanggal_kembali'     => null,
                            'status_pinjam'       => 'Dipinjam',
                            'catatan'             => "Berkas fisik dipinjam untuk {$borrower['reason']}. Pengembalian diawasi Admin Warehouse.",
                        ]);
                    }

                    // Untuk sebagian dokumen terverifikasi, tambahkan riwayat peminjaman masa lalu yang sudah selesai
                    if ($status === 'Terverifikasi & Diarsipkan' && $i % 15 === 0) {
                        $pastBorrower = $borrowersPool[($i + 2) % count($borrowersPool)];
                        BorrowingLog::create([
                            'document_id'         => $doc->id,
                            'handled_by'          => $admin->id,
                            'returned_to_user_id' => $admin->id,
                            'nama_peminjam'       => $pastBorrower['name'],
                            'departemen_peminjam' => $pastBorrower['dept'],
                            'keperluan'           => $pastBorrower['reason'],
                            'tanggal_pinjam'      => $tanggalPb->copy()->addDays(5)->format('Y-m-d'),
                            'batas_kembali'       => $tanggalPb->copy()->addDays(12)->format('Y-m-d'),
                            'tanggal_kembali'     => $tanggalPb->copy()->addDays(10)->format('Y-m-d'),
                            'status_pinjam'       => 'Dikembalikan',
                            'is_form_pb_lengkap'  => true,
                            'is_lampiran_lengkap' => true,
                            'kondisi_fisik'       => 'Baik & Utuh',
                            'catatan'             => 'Dokumen fisik dikembalikan tepat waktu dalam kondisi utuh dan bersih ke ordner.',
                        ]);
                    }

                    // Audit Trail ActivityLog berkala
                    if ($i % 6 === 0) {
                        ActivityLog::create([
                            'user_id'       => $doc->user_id,
                            'activity_type' => ($status === 'Terverifikasi & Diarsipkan') ? 'verify_physical' : 'create_document',
                            'description'   => "Pencatatan dan pemrosesan berkas {$doc->nomor_pb} ({$doc->bagian})",
                            'subject_type'  => Document::class,
                            'subject_id'    => $doc->id,
                            'ip_address'    => '127.0.0.1',
                        ]);
                    }
                }
            }

            // 6. Sinkronisasi status keterisian box (kunci otomatis jika dokumen >= kapasitas maksimal)
            foreach (Box::all() as $b) {
                $count = $b->documents()->count();
                if ($count >= $b->kapasitas_maksimal) {
                    $b->update(['status' => 'Penuh (Terkunci)']);
                } else {
                    $b->update(['status' => 'Tersedia']);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Menyediakan katalog dataset otentik operasional oilfield PT Besmindo Materi Sewatama
     */
    private function getCatalogData(): array
    {
        return [
            // -------------------------------------------------------------
            // 1. WORKSHOP MACHINING & THREADING (BMS-MCH)
            // -------------------------------------------------------------
            'BMS-MCH' => [
                [
                    'applicant_name' => 'Agus Pratama',
                    'applicant_nip'  => 'BMS-MCH-0104',
                    'description'    => 'Pengadaan Carbide Turning Insert Sandvik Coromant untuk pembubutan thread sambungan casing pipa.',
                    'items' => [
                        ['nama' => 'Carbide Insert Sandvik WNMG 080408-PM', 'part_number' => 'SNV-WNMG-0804', 'jumlah' => 30, 'satuan' => 'Pcs', 'harga_satuan' => 145000, 'spesifikasi' => 'Grade GC4325 untuk pemotongan pipa baja keras'],
                        ['nama' => 'Carbide Insert Sandvik CNMG 120408-PR', 'part_number' => 'SNV-CNMG-1204', 'jumlah' => 20, 'satuan' => 'Pcs', 'harga_satuan' => 155000, 'spesifikasi' => 'Grade GC4315 untuk finishing permukaan pin & box'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_MCH_Sandvik_Insert.pdf', 'size' => '1.8 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => 'PO_Sandvik_PT_Kawan_Lama.pdf', 'size' => '850 KB'],
                        ['jenis' => 'Surat Jalan / Delivery Order (DO)', 'name' => 'DO_Kawan_Lama_Inserts.pdf', 'size' => '620 KB'],
                    ],
                ],
                [
                    'applicant_name' => 'Heri Purwanto',
                    'applicant_nip'  => 'BMS-MCH-0112',
                    'description'    => 'Pengadaan Threading Insert Carmex 16ER & 22ER untuk pembuatan ulir API 8-Round Tubing & Casing.',
                    'items' => [
                        ['nama' => 'Carmex Threading Insert 16ER 8 RD', 'part_number' => 'CRX-16ER-8RD', 'jumlah' => 25, 'satuan' => 'Pcs', 'harga_satuan' => 185000, 'spesifikasi' => 'Submicron grade BXC untuk ulir API Spec 5B'],
                        ['nama' => 'Carmex Threading Insert 22ER 8 RD', 'part_number' => 'CRX-22ER-8RD', 'jumlah' => 15, 'satuan' => 'Pcs', 'harga_satuan' => 245000, 'spesifikasi' => 'External threading casing 7" s/d 9-5/8"'],
                    ],
                ],
                [
                    'applicant_name' => 'Tri Wahyudi',
                    'applicant_nip'  => 'BMS-MCH-0128',
                    'description'    => 'Pengadaan Billet Raw Material AISI 4145H Modified Alloy Steel untuk pembuatan Crossover Sub & Bit Sub.',
                    'items' => [
                        ['nama' => 'Round Bar AISI 4145H Mod 8-1/2" OD x 3000mm', 'part_number' => 'STL-4145H-850', 'jumlah' => 2, 'satuan' => 'Batang', 'harga_satuan' => 38500000, 'spesifikasi' => 'Quenched & Tempered 285-341 HBW, Mill Test Cert dilampirkan'],
                        ['nama' => 'Round Bar AISI 4145H Mod 7" OD x 3000mm',     'part_number' => 'STL-4145H-700', 'jumlah' => 2, 'satuan' => 'Batang', 'harga_satuan' => 27500000, 'spesifikasi' => 'Yield Strength min 110,000 PSI untuk drill string subs'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_MCH_Billet_4145H.pdf', 'size' => '2.1 MB'],
                        ['jenis' => 'Sertifikat Mill Test Certificate (MTC)', 'name' => 'MTC_Timken_Steel_4145H_Heat992.pdf', 'size' => '1.4 MB'],
                    ],
                ],
                [
                    'applicant_name' => 'Bambang Suryono',
                    'applicant_nip'  => 'BMS-MCH-0145',
                    'description'    => 'Pengadaan Cutting Fluid Semi-Synthetic Soluble Oil Castrol Alusol untuk sirkulasi pendingin CNC Lathe Mazak.',
                    'items' => [
                        ['nama' => 'Castrol Alusol SL 51 XBB Metalworking Fluid', 'part_number' => 'CST-ALUSOL-200L', 'jumlah' => 2, 'satuan' => 'Drum', 'harga_satuan' => 9850000, 'spesifikasi' => 'Drum 200 Liter, bio-stable formula tanpa boron & biosida'],
                        ['nama' => 'Castrol Magna BD 68 Slideway Oil',           'part_number' => 'CST-MAGNA-20L',   'jumlah' => 4, 'satuan' => 'Pail', 'harga_satuan' => 1750000, 'spesifikasi' => 'Pail 20 Liter ISO VG 68 untuk pelumasan rel guideway mesin'],
                    ],
                ],
                [
                    'applicant_name' => 'Denny Kurniawan',
                    'applicant_nip'  => 'BMS-MCH-0158',
                    'description'    => 'Pengadaan Solid Carbide Endmill Kennametal & Indexable Chamfer Mill untuk pembuatan alur stabilizer spiral.',
                    'items' => [
                        ['nama' => 'Kennametal Solid Carbide Endmill 20mm 4-Flute', 'part_number' => 'KNM-EM20-4FL', 'jumlah' => 6, 'satuan' => 'Pcs', 'harga_satuan' => 1850000, 'spesifikasi' => 'K313 TiAlN Coated untuk baja keras 4145H'],
                        ['nama' => 'Ball Nose Carbide Endmill 16mm R8',             'part_number' => 'KNM-BN16-R8',  'jumlah' => 4, 'satuan' => 'Pcs', 'harga_satuan' => 1450000, 'spesifikasi' => 'Untuk finishing profil flute spiral stabilizer'],
                    ],
                ],
                [
                    'applicant_name' => 'Agus Pratama',
                    'applicant_nip'  => 'BMS-MCH-0104',
                    'description'    => 'Pengadaan Hardfacing Tungsten Carbide Composite Rod Kutrite untuk redressing stabilizer blade rig pemboran.',
                    'items' => [
                        ['nama' => 'Tungsten Carbide Composite Rod Kutrite 1/4" Mesh', 'part_number' => 'KUT-ROD-14M', 'jumlah' => 50, 'satuan' => 'Kg', 'harga_satuan' => 650000, 'spesifikasi' => 'Matrix perunggu-nikel dengan butiran sintered tungsten carbide'],
                        ['nama' => 'Arnco 100XT Hardbanding Flux-Cored Wire 1/16"',   'part_number' => 'ARN-100XT-SPOOL', 'jumlah' => 4, 'satuan' => 'Spool', 'harga_satuan' => 8450000, 'spesifikasi' => 'Spool 25 Kg kawat las hardbanding drill pipe casing friendly'],
                    ],
                ],
                [
                    'applicant_name' => 'Heri Purwanto',
                    'applicant_nip'  => 'BMS-MCH-0112',
                    'description'    => 'Pengadaan Threading Chaser Buttress Casing untuk mesin Lathe Hollow Spindle CMT Urpe.',
                    'items' => [
                        ['nama' => 'Chaser Set API Buttress Casing 5 TPI 3/4" TPF', 'part_number' => 'CHS-BTC-5TPI', 'jumlah' => 4, 'satuan' => 'Set', 'harga_satuan' => 6200000, 'spesifikasi' => 'Material M42 High Speed Steel Coated TiN'],
                        ['nama' => 'Thread Turning Insert 27ER 5.0 BUTTRESS',       'part_number' => 'VAR-27ER-5BTC', 'jumlah' => 15, 'satuan' => 'Pcs', 'harga_satuan' => 320000, 'spesifikasi' => 'Carbide insert untuk ulir pin casing 9-5/8"'],
                    ],
                ],
                [
                    'applicant_name' => 'Tri Wahyudi',
                    'applicant_nip'  => 'BMS-MCH-0128',
                    'description'    => 'Pengadaan Non-Magnetic Stainless Steel Round Bar P530 untuk collar Directional MWD.',
                    'items' => [
                        ['nama' => 'Non-Mag Stainless Steel P530 6-3/4" OD x 2000mm', 'part_number' => 'NMG-P530-675', 'jumlah' => 1, 'satuan' => 'Batang', 'harga_satuan' => 54000000, 'spesifikasi' => 'Permeabilitas magnetik < 1.005, Standar API Spec 7-1'],
                    ],
                ],
                [
                    'applicant_name' => 'Bambang Suryono',
                    'applicant_nip'  => 'BMS-MCH-0145',
                    'description'    => 'Pengadaan Soft Jaws & Chuck T-Nuts 15-inch untuk CNC Lathe Kitagawa BB215.',
                    'items' => [
                        ['nama' => 'Kitagawa Soft Jaws Serrated 1.5mm x 60 deg 15"', 'part_number' => 'KTG-SJ-15', 'jumlah' => 6, 'satuan' => 'Set', 'harga_satuan' => 1450000, 'spesifikasi' => 'Set isi 3 pcs baja lunak untuk pencekaman pipa casing'],
                        ['nama' => 'T-Nut Chuck M16 Heavy Duty Kitagawa',            'part_number' => 'KTG-TNUT-M16', 'jumlah' => 6, 'satuan' => 'Pcs', 'harga_satuan' => 450000, 'spesifikasi' => 'Hardened steel 4140 untuk locking chuck jaw'],
                    ],
                ],
                [
                    'applicant_name' => 'Denny Kurniawan',
                    'applicant_nip'  => 'BMS-MCH-0158',
                    'description'    => 'Pengadaan Dial Test Indicator Mitutoyo 0.01mm dan Magnetic Stand untuk kelurusan alignment pipa bubut.',
                    'items' => [
                        ['nama' => 'Mitutoyo Dial Test Indicator 0.01mm (513-404-10E)', 'part_number' => 'MTY-513-404', 'jumlah' => 3, 'satuan' => 'Unit', 'harga_satuan' => 1350000, 'spesifikasi' => 'Akurasi 0.01mm range 0.8mm dengan sertifikat kalibrasi pabrik'],
                        ['nama' => 'Mitutoyo Magnetic Stand Heavy Duty (7011SN)',       'part_number' => 'MTY-7011SN',  'jumlah' => 3, 'satuan' => 'Unit', 'harga_satuan' => 850000, 'spesifikasi' => 'Daya rekat magnet 600N dengan fine adjustment knob'],
                    ],
                ],
            ],

            // -------------------------------------------------------------
            // 2. NDT & TUBULAR INSPECTION SERVICES (BMS-NDT)
            // -------------------------------------------------------------
            'BMS-NDT' => [
                [
                    'applicant_name' => 'Dedi Suhendar, S.T.',
                    'applicant_nip'  => 'BMS-NDT-0082',
                    'description'    => 'Pengadaan aerosol Magnetic Particle Inspection Magnaflux 7HF & WCP-2 untuk inspeksi crack drill pipe.',
                    'items' => [
                        ['nama' => 'Magnaflux 7HF Black Magnetic Ink Spray 400ml', 'part_number' => 'MGX-7HF-400ML', 'jumlah' => 48, 'satuan' => 'Can', 'harga_satuan' => 285000, 'spesifikasi' => 'Aerosol spray kaleng partikel magnetik hitam pekat'],
                        ['nama' => 'Magnaflux WCP-2 White Contrast Paint 400ml',   'part_number' => 'MGX-WCP2-400ML', 'jumlah' => 36, 'satuan' => 'Can', 'harga_satuan' => 260000, 'spesifikasi' => 'Cat latar putih kontras cepat kering tanpa retak'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_NDT_Magnaflux.pdf', 'size' => '2.4 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => 'PO_Magnaflux_Asia_MPI.pdf', 'size' => '920 KB'],
                    ],
                ],
                [
                    'applicant_name' => 'Rahmat Hidayat',
                    'applicant_nip'  => 'BMS-NDT-0091',
                    'description'    => 'Pengadaan couplant gel ultrasonik Sonotech Ultragel II & probe dual element Olympus untuk cek ketebalan dinding pipa.',
                    'items' => [
                        ['nama' => 'Sonotech Ultragel II Ultrasonic Couplant Pail 5 Gal', 'part_number' => 'SNT-UG2-5GAL', 'jumlah' => 2, 'satuan' => 'Pail', 'harga_satuan' => 3450000, 'spesifikasi' => 'High viscosity non-toxic couplant gel standar ASTM E797'],
                        ['nama' => 'Olympus DHC713-RM Dual Element Transducer 5MHz',      'part_number' => 'OLY-DHC713',   'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 4850000, 'spesifikasi' => 'Probe 0.375" diameter kabel Microdot terintegrasi'],
                    ],
                ],
                [
                    'applicant_name' => 'Arif Setiawan',
                    'applicant_nip'  => 'BMS-NDT-0103',
                    'description'    => 'Pengadaan aerosol Dye Penetrant Spotcheck SKL-SP2, Developer SKD-S2, dan Cleaner SKC-S.',
                    'items' => [
                        ['nama' => 'Spotcheck SKL-SP2 Solvent Removable Red Penetrant', 'part_number' => 'SPT-SKL-400ML', 'jumlah' => 24, 'satuan' => 'Can', 'harga_satuan' => 240000, 'spesifikasi' => 'Kaleng spray 400ml merah pekat sensitivitas Level 2'],
                        ['nama' => 'Spotcheck SKD-S2 Non-Aqueous Developer 400ml',      'part_number' => 'SPT-SKD-400ML', 'jumlah' => 24, 'satuan' => 'Can', 'harga_satuan' => 250000, 'spesifikasi' => 'Kaleng spray suspensi putih penarik kapiler penetran'],
                        ['nama' => 'Spotcheck SKC-S Solvent Cleaner / Remover 400ml',   'part_number' => 'SPT-SKC-400ML', 'jumlah' => 24, 'satuan' => 'Can', 'harga_satuan' => 210000, 'spesifikasi' => 'Pembersih sisa minyak & penetran tanpa residu'],
                    ],
                ],
                [
                    'applicant_name' => 'Eko Prasetyo',
                    'applicant_nip'  => 'BMS-NDT-0115',
                    'description'    => 'Pengadaan Portable AC/DC Magnetic Yoke Parker B300 untuk inspeksi area bengkel dan rig yard.',
                    'items' => [
                        ['nama' => 'Parker B300 Contour Probe AC/DC Magnetic Yoke', 'part_number' => 'PRK-B300-230V', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 14850000, 'spesifikasi' => 'Tegangan 230V 50Hz, lifting power 10 lbs (AC) & 40 lbs (DC)'],
                        ['nama' => 'Kaki Yoke Double Jointed Articulated Spare',     'part_number' => 'PRK-LEGS-B300', 'jumlah' => 2, 'satuan' => 'Set',  'harga_satuan' => 2350000,  'spesifikasi' => 'Kaki fleksibel untuk inspeksi geometri kurva sambungan pipa'],
                    ],
                ],
                [
                    'applicant_name' => 'Doni Firmansyah',
                    'applicant_nip'  => 'BMS-NDT-0126',
                    'description'    => 'Pengadaan Labino UV Torch LED 365nm High Intensity untuk Magnetic Particle Fluorescent Fluoresen 14A.',
                    'items' => [
                        ['nama' => 'Labino UVX High Intensity LED UV Torch 365nm', 'part_number' => 'LBN-UVX-365', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 12500000, 'spesifikasi' => 'Intensitas > 4500 uW/cm² pada jarak 38cm, standar ISO 3059'],
                        ['nama' => 'Magnaflux 14A Fluorescent Magnetic Powder 1kg', 'part_number' => 'MGX-14A-1KG',  'jumlah' => 3, 'satuan' => 'Can',  'harga_satuan' => 2850000,  'spesifikasi' => 'Partikel hijau fluoresen untuk metode basah bak oli'],
                    ],
                ],
                [
                    'applicant_name' => 'Dedi Suhendar, S.T.',
                    'applicant_nip'  => 'BMS-NDT-0082',
                    'description'    => 'Pengadaan API Spec 5B Thread Pitch Gauge & Lead Gauge untuk verifikasi geometri ulir connection.',
                    'items' => [
                        ['nama' => 'Gagemaker Internal Thread Lead Gauge (LG-5002)', 'part_number' => 'GMK-LG-5002', 'jumlah' => 1, 'satuan' => 'Unit', 'harga_satuan' => 16500000, 'spesifikasi' => 'Rentang 2" s/d 20" diameter dengan dial indicator akurasi 0.0001"'],
                        ['nama' => 'Lead Gauge Contact Points 0.072" Ball (T072)',   'part_number' => 'GMK-T072-BALL', 'jumlah' => 4, 'satuan' => 'Pcs',  'harga_satuan' => 750000,   'spesifikasi' => 'Contact point carbide untuk thread 8-Round API casing'],
                    ],
                ],
                [
                    'applicant_name' => 'Rahmat Hidayat',
                    'applicant_nip'  => 'BMS-NDT-0091',
                    'description'    => 'Pengadaan Borescope Industri Layar HD 5-inch probe 3 meter untuk inspeksi visual internal tube drill pipe.',
                    'items' => [
                        ['nama' => 'Industrial Articulating Video Borescope 3-Meter', 'part_number' => 'BSC-HD-3M6MM', 'jumlah' => 1, 'satuan' => 'Unit', 'harga_satuan' => 18900000, 'spesifikasi' => 'Probe 6.0mm dengan artikulasi 360 derajat layar IPS 5 inch tahan oli'],
                    ],
                ],
                [
                    'applicant_name' => 'Arif Setiawan',
                    'applicant_nip'  => 'BMS-NDT-0103',
                    'description'    => 'Pengadaan Centrifuge Tube 100ml Pear Shaped ASTM D96 dan Stand Aluminium untuk uji konsentrasi partikel.',
                    'items' => [
                        ['nama' => 'Centrifuge Tube 100ml Pear Shaped ASTM D96', 'part_number' => 'MGX-CENT-100ML', 'jumlah' => 3, 'satuan' => 'Pcs', 'harga_satuan' => 1250000, 'spesifikasi' => 'Graduasi presisi 0.05ml pada stem bawah untuk uji suspensi MPI'],
                        ['nama' => 'Centrifuge Stand Aluminium Heavy Duty',      'part_number' => 'MGX-CENT-STND',  'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 850000,  'spesifikasi' => 'Dudukan stabil untuk settling test suspensi magnetik'],
                    ],
                ],
            ],

            // -------------------------------------------------------------
            // 3. WAREHOUSE & LOGISTIK YARD (BMS-WH)
            // -------------------------------------------------------------
            'BMS-WH' => [
                [
                    'applicant_name' => 'Rudi Hartono',
                    'applicant_nip'  => 'BMS-LOG-0031',
                    'description'    => 'Pengadaan Composite Heavy Duty Thread Protector Pin & Box 9-5/8" Casing & 5" Drill Pipe NC50.',
                    'items' => [
                        ['nama' => 'Composite Thread Protector 9-5/8" Buttress Pin & Box', 'part_number' => 'PRT-958-BTC', 'jumlah' => 80, 'satuan' => 'Pasang', 'harga_satuan' => 135000, 'spesifikasi' => 'Baja bertulang resin komposit tahan benturan standar API Spec 5CT Annex I'],
                        ['nama' => 'Composite Thread Protector 5" Drill Pipe NC50 Pin & Box', 'part_number' => 'PRT-500-NC50', 'jumlah' => 60, 'satuan' => 'Pasang', 'harga_satuan' => 165000, 'spesifikasi' => 'Pelindung ulir putar heavy duty untuk penyimpanan yard terbuka'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_WH_Thread_Protectors.pdf', 'size' => '1.9 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => 'PO_Protector_Vendor_Logistik.pdf', 'size' => '820 KB'],
                        ['jenis' => 'Surat Jalan / Delivery Order (DO)', 'name' => 'DO_Trucking_Protector.pdf', 'size' => '540 KB'],
                    ],
                ],
                [
                    'applicant_name' => 'Wahyu Setiadi',
                    'applicant_nip'  => 'BMS-LOG-0062',
                    'description'    => 'Pengadaan Tali Webbing Sling Double Ply 5-Ton dan 10-Ton untuk mobilisasi pipa drill collar.',
                    'items' => [
                        ['nama' => 'Webbing Sling Double Ply 5-Ton x 6-Meter SF 7:1',  'part_number' => 'SLG-5T-6M',  'jumlah' => 10, 'satuan' => 'Pcs', 'harga_satuan' => 485000,  'spesifikasi' => 'Material 100% PES Polyester bersertifikat uji beban Migas'],
                        ['nama' => 'Webbing Sling Double Ply 10-Ton x 6-Meter SF 7:1', 'part_number' => 'SLG-10T-6M', 'jumlah' => 6,  'satuan' => 'Pcs', 'harga_satuan' => 1150000, 'spesifikasi' => 'Kapasitas angkat 10 Ton untuk crane overhead dan rigger yard'],
                    ],
                ],
                [
                    'applicant_name' => 'Ilham Ramadhan',
                    'applicant_nip'  => 'BMS-LOG-0078',
                    'description'    => 'Pengadaan Crosby G-209 Screw Pin Bow Shackle 4.75 Ton & 8.5 Ton untuk rigging alat berat.',
                    'items' => [
                        ['nama' => 'Crosby G-209 Screw Pin Bow Shackle 4.75T WLL', 'part_number' => 'CSB-G209-475', 'jumlah' => 16, 'satuan' => 'Pcs', 'harga_satuan' => 285000, 'spesifikasi' => 'Forged steel galvanized dengan kode batch trace original Crosby USA'],
                        ['nama' => 'Crosby G-209 Screw Pin Bow Shackle 8.5T WLL',  'part_number' => 'CSB-G209-850', 'jumlah' => 8,  'satuan' => 'Pcs', 'harga_satuan' => 650000, 'spesifikasi' => 'Grade 6 quenched & tempered dengan faktor keselamatan 6:1'],
                    ],
                ],
                [
                    'applicant_name' => 'Joko Susilo',
                    'applicant_nip'  => 'BMS-LOG-0085',
                    'description'    => 'Pengadaan Kayu Balok Ganjal Dunnage Hardwood 4"x4"x8ft ISPM-15 untuk racking pipa casing yard.',
                    'items' => [
                        ['nama' => 'Timber Hardwood Dunnage 4"x4"x8ft Treated ISPM-15', 'part_number' => 'TIM-DNG-448', 'jumlah' => 60, 'satuan' => 'Batang', 'harga_satuan' => 145000, 'spesifikasi' => 'Kayu keras tahan cuaca bebas hama serangga dengan stempel IPPC'],
                        ['nama' => 'Molded Polyurethane Pipe Chocks Heavy Duty',       'part_number' => 'CHK-POLY-HD',  'jumlah' => 120, 'satuan' => 'Pcs',    'harga_satuan' => 65000,  'spesifikasi' => 'Ganjal pipa anti gelinding untuk tubular diameter 5" s/d 13"'],
                    ],
                ],
                [
                    'applicant_name' => 'Anton Wijaya',
                    'applicant_nip'  => 'BMS-LOG-0094',
                    'description'    => 'Pengadaan Barcode Thermal Ribbon Zebra Wax-Resin dan Stiker Polyart tahan oli untuk label rak.',
                    'items' => [
                        ['nama' => 'Zebra Ribbon Wax-Resin 110mm x 300m Core 1"',      'part_number' => 'ZBR-WXR-110',   'jumlah' => 8,  'satuan' => 'Roll', 'harga_satuan' => 155000, 'spesifikasi' => 'Hasil cetak tahan gesekan dan cipratan oli cutting'],
                        ['nama' => 'Label Stiker Polyart Outdoor 100mm x 75mm Roll',  'part_number' => 'LBL-PLY-10075', 'jumlah' => 12, 'satuan' => 'Roll', 'harga_satuan' => 245000, 'spesifikasi' => 'Isi 1.000 stiker tahan UV dan cuaca ekstrem pipa yard'],
                    ],
                ],
                [
                    'applicant_name' => 'Rudi Hartono',
                    'applicant_nip'  => 'BMS-LOG-0031',
                    'description'    => 'Pengadaan Ratchet Lashing Straps 5-Ton 50mm x 10m untuk pengikatan kargo pipa di trailer truk.',
                    'items' => [
                        ['nama' => 'Ratchet Cargo Lashing Strap 5T x 10M Double J-Hook', 'part_number' => 'LSH-5T-10M', 'jumlah' => 15, 'satuan' => 'Set', 'harga_satuan' => 275000, 'spesifikasi' => 'Polyester strap 50mm dengan ratchet heavy duty galvanis'],
                        ['nama' => 'Corner Protector Plastic untuk Lashing Belt',       'part_number' => 'CNR-PRT-PLST', 'jumlah' => 60, 'satuan' => 'Pcs', 'harga_satuan' => 15000,  'spesifikasi' => 'Pelindung sudut pipa tajam agar tali lashing tidak sobek'],
                    ],
                ],
                [
                    'applicant_name' => 'Wahyu Setiadi',
                    'applicant_nip'  => 'BMS-LOG-0062',
                    'description'    => 'Pengadaan Terpal PVC Tarpaulin 650 GSM 10m x 12m tahan air untuk penutup tumpukan pup joint ekspor.',
                    'items' => [
                        ['nama' => 'Heavy Duty PVC Tarpaulin Canvas 650 GSM 10x12m', 'part_number' => 'TRP-PVC-1012', 'jumlah' => 4, 'satuan' => 'Pcs', 'harga_satuan' => 2850000, 'spesifikasi' => 'Bahan terpal parasut karet tebal warna biru-orange ring mata ayam'],
                        ['nama' => 'Tali Tambang Nilon Pengikat Terpal 10mm x 100m', 'part_number' => 'ROP-NYL-1010', 'jumlah' => 3, 'satuan' => 'Roll', 'harga_satuan' => 380000,  'spesifikasi' => 'Serat nilon berkekuatan tarik tinggi tahan sinar matahari'],
                    ],
                ],
                [
                    'applicant_name' => 'Ilham Ramadhan',
                    'applicant_nip'  => 'BMS-LOG-0078',
                    'description'    => 'Pengadaan Timbangan Crane Digital Gantung Wireless 10-Ton untuk penimbangan beban bundel pipa.',
                    'items' => [
                        ['nama' => 'Digital Crane Hanging Scale Wireless 10-Ton', 'part_number' => 'SCL-CRN-10T', 'jumlah' => 1, 'satuan' => 'Unit', 'harga_satuan' => 14500000, 'spesifikasi' => 'Layar remote kontrol handheld, sertifikat tera metrologi legal'],
                    ],
                ],
            ],

            // -------------------------------------------------------------
            // 4. HSE & K3L (BMS-HSE)
            // -------------------------------------------------------------
            'BMS-HSE' => [
                [
                    'applicant_name' => 'Fajar Nugroho, S.K.M.',
                    'applicant_nip'  => 'BMS-HSE-0056',
                    'description'    => 'Pengadaan Coverall Nomex IIIA Flame Retardant 4.5 oz warna Orange untuk teknisi workshop dan yard.',
                    'items' => [
                        ['nama' => 'Nomex IIIA Flame Retardant Coverall Orange Size L',  'part_number' => 'NMX-FR-ORG-L',  'jumlah' => 25, 'satuan' => 'Pcs', 'harga_satuan' => 1250000, 'spesifikasi' => 'Standar NFPA 2112 & EN ISO 11612 lengkap pita reflektif 3M'],
                        ['nama' => 'Nomex IIIA Flame Retardant Coverall Orange Size XL', 'part_number' => 'NMX-FR-ORG-XL', 'jumlah' => 15, 'satuan' => 'Pcs', 'harga_satuan' => 1250000, 'spesifikasi' => 'Kain DuPont Nomex tahan api permanen dengan bordir logo Besmindo'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_HSE_Coverall_Nomex.pdf', 'size' => '1.6 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => 'PO_DuPont_Nomex_Distributor.pdf', 'size' => '780 KB'],
                    ],
                ],
                [
                    'applicant_name' => 'Bagus Santoso',
                    'applicant_nip'  => 'BMS-HSE-0067',
                    'description'    => 'Pengadaan Multi-Gas Detector 4-Gas BW Honeywell GasAlertMicroClip XL dan Gas Kalibrasi.',
                    'items' => [
                        ['nama' => 'BW Honeywell GasAlertMicroClip XL 4-Gas (O2/LEL/CO/H2S)', 'part_number' => 'BWH-MCXL-4G',   'jumlah' => 3, 'satuan' => 'Unit', 'harga_satuan' => 9850000, 'spesifikasi' => 'Detektor gas personal submersible IP68 dengan sensor H2S respons cepat'],
                        ['nama' => 'Tabung Kalibrasi Multi-Gas Mix 34-Liter (H2S/CO/CH4/O2)', 'part_number' => 'CAL-GAS-34L',   'jumlah' => 2, 'satuan' => 'Can',  'harga_satuan' => 4750000, 'spesifikasi' => 'Campuran uji sensor 25ppm H2S, 100ppm CO, 2.5% Methane, 18% O2'],
                        ['nama' => 'Regulator Tabung Gas Kalibrasi Demand Flow 0.5 LPM',       'part_number' => 'REG-GAS-DF05',  'jumlah' => 1, 'satuan' => 'Unit', 'harga_satuan' => 2450000, 'spesifikasi' => 'Bahan kuningan lapis nikel koneksi tabung C10'],
                    ],
                ],
                [
                    'applicant_name' => 'Rian Hidayat',
                    'applicant_nip'  => 'BMS-HSE-0072',
                    'description'    => 'Pengadaan Oil Spill Response Kit 240-Liter Bin Roda dan Absorbent Pads untuk penanganan tumpahan oli.',
                    'items' => [
                        ['nama' => 'Oil Spill Kit 240-Liter Wheeled Mobile Container', 'part_number' => 'SPL-KIT-240L', 'jumlah' => 2, 'satuan' => 'Set',  'harga_satuan' => 5850000, 'spesifikasi' => 'Tempat sampah kuning roda berisi boom 3m, bantal, pad, kacamata, sarung tangan'],
                        ['nama' => 'Oil Only White Absorbent Pads 40x50cm (Bale 100)', 'part_number' => 'ABS-PAD-100',  'jumlah' => 6, 'satuan' => 'Bale', 'harga_satuan' => 850000,  'spesifikasi' => 'Daya serap 120 liter hidrokarbon per bale, menolak air'],
                    ],
                ],
                [
                    'applicant_name' => 'Hendra Saputra',
                    'applicant_nip'  => 'BMS-HSE-0088',
                    'description'    => 'Pengadaan Alat Pemadam Api Ringan (APAR) Yamato Powder 6kg & 9kg untuk area gudang pelumas.',
                    'items' => [
                        ['nama' => 'APAR Dry Chemical Powder ABC 6kg Yamato', 'part_number' => 'YMT-ABC-6KG', 'jumlah' => 8, 'satuan' => 'Unit', 'harga_satuan' => 950000,  'spesifikasi' => 'Standar Damkar kelas kebakaran A, B, C dengan pressure gauge'],
                        ['nama' => 'APAR Dry Chemical Powder ABC 9kg Yamato', 'part_number' => 'YMT-ABC-9KG', 'jumlah' => 4, 'satuan' => 'Unit', 'harga_satuan' => 1350000, 'spesifikasi' => 'Tabung tanpa las lasan (seamless) lengkap selang dan nozzle'],
                    ],
                ],
                [
                    'applicant_name' => 'Maya Kusuma, S.T.',
                    'applicant_nip'  => 'BMS-HSE-0093',
                    'description'    => 'Pengadaan Sepatu Safety Boots King’s KWD 805 & Kacamata 3M Virtua Anti-Fog.',
                    'items' => [
                        ['nama' => 'Sepatu Safety Boots Kulit King’s KWD 805 Black', 'part_number' => 'KNG-KWD-805', 'jumlah' => 20, 'satuan' => 'Pasang', 'harga_satuan' => 485000, 'spesifikasi' => 'Steel toe cap 200 Joules & pelat baja anti paku midsole'],
                        ['nama' => 'Safety Glasses 3M Virtua Anti-Fog Clear',        'part_number' => '3M-VRT-11228', 'jumlah' => 40, 'satuan' => 'Pcs',    'harga_satuan' => 45000,  'spesifikasi' => 'Lensa polikarbonat bening perlindungan UV99.9%'],
                    ],
                ],
                [
                    'applicant_name' => 'Fajar Nugroho, S.K.M.',
                    'applicant_nip'  => 'BMS-HSE-0056',
                    'description'    => 'Pengadaan Emergency Escape Breathing Apparatus (EEBA) Scott 15-Menit untuk kesiapsiagaan gas H2S.',
                    'items' => [
                        ['nama' => 'Scott Safety ELSA 15-Minute EEBA Escape Set', 'part_number' => 'SCT-ELSA-15M', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 11500000, 'spesifikasi' => 'Silinder udara tekan 200 bar dengan tudung kepala anti tembus H2S'],
                    ],
                ],
                [
                    'applicant_name' => 'Bagus Santoso',
                    'applicant_nip'  => 'BMS-HSE-0067',
                    'description'    => 'Pengadaan Safety Harness Karam PN 56 Double Lanyard Shock Absorber untuk rigger crane.',
                    'items' => [
                        ['nama' => 'Karam PN 56 Full Body Harness Double Big Hook', 'part_number' => 'KAR-PN56-FBH', 'jumlah' => 6, 'satuan' => 'Set', 'harga_satuan' => 920000, 'spesifikasi' => 'Standar EN 361 lengkap penyerap energi saat jatuh bebas'],
                    ],
                ],
            ],

            // -------------------------------------------------------------
            // 5. MAINTENANCE & UTILITY (BMS-MTN)
            // -------------------------------------------------------------
            'BMS-MTN' => [
                [
                    'applicant_name' => 'Imran Rosyadi',
                    'applicant_nip'  => 'BMS-MTN-0074',
                    'description'    => 'Pengadaan Bearing SKF Spherical Roller & Grease Shell Gadus untuk overhead crane 20 Ton Bay 1.',
                    'items' => [
                        ['nama' => 'SKF Spherical Roller Bearing 22216 EK', 'part_number' => 'SKF-22216-EK', 'jumlah' => 4, 'satuan' => 'Unit', 'harga_satuan' => 2450000, 'spesifikasi' => 'Tapered bore include adapter sleeve H316 original SKF Sweden'],
                        ['nama' => 'Shell Gadus S2 V220 2 Extreme Pressure Grease', 'part_number' => 'SHL-GDS-18KG', 'jumlah' => 2, 'satuan' => 'Pail', 'harga_satuan' => 1650000, 'spesifikasi' => 'Pail 18 Kg pelumas gemuk tahan tekanan ekstrim dan beban kejut'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_MTN_SKF_Bearing.pdf', 'size' => '2.2 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => 'PO_SKF_Authorized_Distributor.pdf', 'size' => '890 KB'],
                        ['jenis' => 'Berita Acara Serah Terima (BAST)', 'name' => 'BAST_Pemasangan_Bearing_Crane.pdf', 'size' => '650 KB'],
                    ],
                ],
                [
                    'applicant_name' => 'Budi Hartono',
                    'applicant_nip'  => 'BMS-MTN-0081',
                    'description'    => 'Pengadaan Kawat Seling Hoist Baja 18mm 6x36 IWRC Galvanis untuk Overhead Crane Utama.',
                    'items' => [
                        ['nama' => 'Wire Rope Hoist 18mm 6x36 IWRC Galvanized 1960 N/mm²', 'part_number' => 'WRP-18MM-100M', 'jumlah' => 100, 'satuan' => 'Meter', 'harga_satuan' => 185000, 'spesifikasi' => 'Inti baja penuh (IWRC) dengan sertifikat putus mill test Kiswire'],
                        ['nama' => 'Klem Seling Heavy Duty US Type Crosby 3/4"',             'part_number' => 'CSB-WRC-34',   'jumlah' => 12,  'satuan' => 'Pcs',   'harga_satuan' => 125000, 'spesifikasi' => 'Drop forged galvanized wire rope clip original'],
                    ],
                ],
                [
                    'applicant_name' => 'Slamet Riyadi',
                    'applicant_nip'  => 'BMS-MTN-0096',
                    'description'    => 'Pengadaan Filter Oli & Filter Solar Donaldson untuk Forklift Toyota 5-Ton dan TCM 7-Ton.',
                    'items' => [
                        ['nama' => 'Donaldson Lube Filter Spin-on P550008',       'part_number' => 'DNL-P550008',  'jumlah' => 8, 'satuan' => 'Pcs', 'harga_satuan' => 245000, 'spesifikasi' => 'Filter oli mesin diesel efisiensi tinggi sintetis'],
                        ['nama' => 'Donaldson Fuel Water Separator Filter P551433', 'part_number' => 'DNL-P551433',  'jumlah' => 8, 'satuan' => 'Pcs', 'harga_satuan' => 380000, 'spesifikasi' => 'Pemisah air bahan bakar solar dengan mangkok kuras'],
                    ],
                ],
                [
                    'applicant_name' => 'Dian Pratama',
                    'applicant_nip'  => 'BMS-MTN-0105',
                    'description'    => 'Pengadaan Oli Hidrolik Shell Tellus S2 MX 68 untuk unit Forklift Kalmar dan Mesin Bubut CNC.',
                    'items' => [
                        ['nama' => 'Shell Tellus S2 MX 68 Industrial Hydraulic Oil', 'part_number' => 'SHL-TL68-DRUM', 'jumlah' => 2, 'satuan' => 'Drum', 'harga_satuan' => 8950000, 'spesifikasi' => 'Drum 209 Liter oli hidrolik mineral anti-wear standar ISO 11158'],
                    ],
                ],
                [
                    'applicant_name' => 'Gunawan Wibowo',
                    'applicant_nip'  => 'BMS-MTN-0118',
                    'description'    => 'Pengadaan Pushbutton Pendant Control Crane 8-Tombol dan Kabel Flat Festoon.',
                    'items' => [
                        ['nama' => 'Schneider Pendant Control Station 8-Buttons XAC-A871', 'part_number' => 'SCH-XACA871',  'jumlah' => 2,  'satuan' => 'Unit',  'harga_satuan' => 3450000, 'spesifikasi' => 'Tombol dual speed tahan benturan IP65 include tombol E-Stop'],
                        ['nama' => 'Kabel Festoon Pipih 4G6 + 8x1.5mm² Flexible',         'part_number' => 'FST-CBL-4G68', 'jumlah' => 40, 'satuan' => 'Meter', 'harga_satuan' => 195000,  'spesifikasi' => 'Kabel fleksibel gantungan rel crane tahan tekukan konstan'],
                    ],
                ],
                [
                    'applicant_name' => 'Imran Rosyadi',
                    'applicant_nip'  => 'BMS-MTN-0074',
                    'description'    => 'Pengadaan Kunci Torsi Pneumatik Air Impact Wrench Chicago Pneumatic 1-inch.',
                    'items' => [
                        ['nama' => 'Chicago Pneumatic CP-7782 1" Heavy Duty Air Impact', 'part_number' => 'CPN-CP7782', 'jumlah' => 1, 'satuan' => 'Unit', 'harga_satuan' => 14500000, 'spesifikasi' => 'Torsi maksimal 2600 Nm dengan 6" extended anvil untuk baut roda alat berat'],
                        ['nama' => 'Mata Kunci Impact Socket Set 1" Drive 24-50mm',      'part_number' => 'SKT-SET-1INCH', 'jumlah' => 1, 'satuan' => 'Set',  'harga_satuan' => 4850000,  'spesifikasi' => 'Material Cr-Mo tebal tahan beban hentakan ekstrim'],
                    ],
                ],
                [
                    'applicant_name' => 'Budi Hartono',
                    'applicant_nip'  => 'BMS-MTN-0081',
                    'description'    => 'Pengadaan Automatic Voltage Regulator (AVR) Stamford SX460 untuk Generator Genset Cummins 250 kVA.',
                    'items' => [
                        ['nama' => 'AVR Stamford SX460 Original Generator Module', 'part_number' => 'STF-AVR-SX460', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 3850000, 'spesifikasi' => 'Modul pengatur tegangan otomatis alternator Stamford UK original'],
                    ],
                ],
            ],

            // -------------------------------------------------------------
            // 6. OPERASIONAL LAPANGAN & RIG SERVICES (BMS-OPS)
            // -------------------------------------------------------------
            'BMS-OPS' => [
                [
                    'applicant_name' => 'Bambang Triyono',
                    'applicant_nip'  => 'BMS-OPS-0023',
                    'description'    => 'Pengadaan Thread Compound Bestolife 2000 Lead-Free dan Jet-Lube Kopr-Kote untuk operasi workover rig.',
                    'items' => [
                        ['nama' => 'Bestolife 2000 Non-Metallic Thread Compound Pail 5 Gal', 'part_number' => 'BST-2000-5GAL', 'jumlah' => 4, 'satuan' => 'Pail', 'harga_satuan' => 3450000, 'spesifikasi' => 'Pail 5 Gallon kompon ulir ramah lingkungan tanpa timbal untuk casing/tubing'],
                        ['nama' => 'Jet-Lube Kopr-Kote Drill Pipe Compound Pail 5 Gal',     'part_number' => 'JTL-KOPR-5GAL', 'jumlah' => 3, 'satuan' => 'Pail', 'harga_satuan' => 4150000, 'spesifikasi' => 'Kompon anti-seize partikel tembaga mikro untuk rotary drill collar subs'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_BMS_OPS_Dope_KoprKote.pdf', 'size' => '1.7 MB'],
                        ['jenis' => 'Purchase Order (PO)', 'name' => 'PO_JetLube_Bestolife_Indo.pdf', 'size' => '810 KB'],
                    ],
                ],
                [
                    'applicant_name' => 'Yusuf Mahendra',
                    'applicant_nip'  => 'BMS-OPS-0035',
                    'description'    => 'Pengadaan Valve & Seat Mud Pump Continental Emsco F-1000 API #7 Premium.',
                    'items' => [
                        ['nama' => 'Triplex Mud Pump Valve & Seat API #7 Premium', 'part_number' => 'EMS-VLV-API7', 'jumlah' => 6, 'satuan' => 'Set', 'harga_satuan' => 3200000, 'spesifikasi' => 'Baja tempa 8620 carburized dengan insert polyurethane tahan lumpur abrasif'],
                        ['nama' => 'Mud Pump Valve Springs Inconel High Temp',    'part_number' => 'EMS-SPR-INC7', 'jumlah' => 6, 'satuan' => 'Pcs', 'harga_satuan' => 450000,  'spesifikasi' => 'Material Inconel 718 tahan panas dan korosi lumpur pemboran'],
                    ],
                ],
                [
                    'applicant_name' => 'Firman Utomo',
                    'applicant_nip'  => 'BMS-OPS-0048',
                    'description'    => 'Pengadaan Ring Gasket API 6A BX-154 & BX-155 untuk Wellhead Flange 10,000 PSI.',
                    'items' => [
                        ['nama' => 'API Ring Joint Gasket BX-154 Soft Iron Zinc Plated', 'part_number' => 'GSK-BX154-SI', 'jumlah' => 8, 'satuan' => 'Pcs', 'harga_satuan' => 650000, 'spesifikasi' => 'Tekanan kerja hingga 10,000 PSI standar API Spec 6A monogram'],
                        ['nama' => 'API Ring Joint Gasket BX-155 Low Carbon Steel',     'part_number' => 'GSK-BX155-CS', 'jumlah' => 6, 'satuan' => 'Pcs', 'harga_satuan' => 750000, 'spesifikasi' => 'Gasket bertekanan tinggi untuk BOP choke & kill manifold'],
                    ],
                ],
                [
                    'applicant_name' => 'Sigit Prabowo',
                    'applicant_nip'  => 'BMS-OPS-0059',
                    'description'    => 'Pengadaan Rotary Tong Dies & Slip Insert Dies untuk handling pipa bor 5-inch.',
                    'items' => [
                        ['nama' => 'Tong Dies 1/2" x 1-1/4" x 5" Diamond Tooth', 'part_number' => 'TNG-DIE-12145', 'jumlah' => 30, 'satuan' => 'Pcs', 'harga_satuan' => 125000, 'spesifikasi' => 'Gigi cengkeram perlakuan panas karburisasi untuk tong manual'],
                        ['nama' => 'Slip Insert Dies Part No 2163 untuk Slip 5"', 'part_number' => 'SLP-DIE-2163',  'jumlah' => 36, 'satuan' => 'Pcs', 'harga_satuan' => 145000, 'spesifikasi' => 'Sisipan penjepit pipa drill pipe pada rotary table'],
                    ],
                ],
                [
                    'applicant_name' => 'Rio Anggoro',
                    'applicant_nip'  => 'BMS-OPS-0064',
                    'description'    => 'Pengadaan Ban All-Terrain BF Goodrich 265/70R17 untuk Armada Light Vehicle Toyota Hilux 4x4 Rig.',
                    'items' => [
                        ['nama' => 'Ban BF Goodrich All-Terrain T/A KO2 265/70R17', 'part_number' => 'BFG-26570-R17', 'jumlah' => 8, 'satuan' => 'Pcs', 'harga_satuan' => 3850000, 'spesifikasi' => 'Ply rating 10PR dinding ban tebal CoreGard untuk medan lumpur rig'],
                        ['nama' => 'Lampu Safety Whip Flag LED 2.4-Meter Tambang/Rig', 'part_number' => 'WHP-FLAG-24M', 'jumlah' => 4, 'satuan' => 'Unit', 'harga_satuan' => 750000,  'spesifikasi' => 'Tiang fiberglass dengan bendera oranye reflektif & lampu LED strobo'],
                    ],
                ],
                [
                    'applicant_name' => 'Bambang Triyono',
                    'applicant_nip'  => 'BMS-OPS-0023',
                    'description'    => 'Pengadaan Drill Pipe Float Valve Baker Style Model F Size 4R untuk sirkulasi fluida pemboran.',
                    'items' => [
                        ['nama' => 'Baker Style Drill Pipe Float Valve Model F 4R', 'part_number' => 'BKR-FLT-F4R', 'jumlah' => 3, 'satuan' => 'Unit', 'harga_satuan' => 4850000, 'spesifikasi' => 'Plunger type non-return valve untuk mencegah backflow lumpur ke dalam pipa bor'],
                    ],
                ],
                [
                    'applicant_name' => 'Yusuf Mahendra',
                    'applicant_nip'  => 'BMS-OPS-0035',
                    'description'    => 'Pengadaan Spinning Chain Baja Paduan 5/16" x 33ft untuk penyambungan pipa bor di rig floor.',
                    'items' => [
                        ['nama' => 'Alloy Spinning Chain 5/16" x 33ft with Pull Rope', 'part_number' => 'SPN-CHN-516', 'jumlah' => 2, 'satuan' => 'Set', 'harga_satuan' => 2850000, 'spesifikasi' => 'Rantai baja khusus tahan hentakan putaran rotary cathead'],
                    ],
                ],
            ],

            // -------------------------------------------------------------
            // 7. QUALITY ASSURANCE / QC (BMS-QC)
            // -------------------------------------------------------------
            'BMS-QC' => [
                [
                    'applicant_name' => 'Maya Indriyani, S.T.',
                    'applicant_nip'  => 'BMS-QA-0019',
                    'description'    => 'Pengadaan Master Calibration Block IIW Type 1 ISO 2400 Carbon Steel untuk kalibrasi alat UT.',
                    'items' => [
                        ['nama' => 'Calibration Block IIW Type 1 Carbon Steel ISO 2400', 'part_number' => 'BLK-IIW-V1-CS', 'jumlah' => 1, 'satuan' => 'Set', 'harga_satuan' => 4950000, 'spesifikasi' => 'Blok kalibrasi standar ISO 2400 lengkap kotak kayu dan sertifikat kalibrasi pabrik'],
                        ['nama' => 'Step Wedge Calibration Block 5-Step (2.5mm - 12.5mm)', 'part_number' => 'BLK-STP-5STEP', 'jumlah' => 2, 'satuan' => 'Set', 'harga_satuan' => 2250000, 'spesifikasi' => 'Material baja karbon 1018 dengan toleransi ketebalan ± 0.02mm'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_QA_Calibration_Block.pdf', 'size' => '2.8 MB'],
                        ['jenis' => 'Sertifikat Kalibrasi / Inspeksi QC', 'name' => 'Cert_Calibration_IIW_Block.pdf', 'size' => '1.2 MB'],
                    ],
                ],
                [
                    'applicant_name' => 'Ricky Firmansyah',
                    'applicant_nip'  => 'BMS-QC-0033',
                    'description'    => 'Pengadaan Master Thread Plug & Ring Gage API Spec 7-2 NC50 untuk validasi koneksi threaded.',
                    'items' => [
                        ['nama' => 'Master Thread Ring & Plug Gage API NC50 Set', 'part_number' => 'GAG-API-NC50', 'jumlah' => 1, 'satuan' => 'Set', 'harga_satuan' => 42500000, 'spesifikasi' => 'Sertifikat NABL / API Monogram terakreditasi ISO 17025'],
                    ],
                    'attachments' => [
                        ['jenis' => 'Form PB Scan Asli', 'name' => 'Scan_PB_QC_Master_Gage_NC50.pdf', 'size' => '2.5 MB'],
                        ['jenis' => 'Sertifikat Kalibrasi / Inspeksi QC', 'name' => 'Cert_Master_Gage_API_NC50.pdf', 'size' => '1.8 MB'],
                    ],
                ],
                [
                    'applicant_name' => 'Aditya Rahman',
                    'applicant_nip'  => 'BMS-QC-0044',
                    'description'    => 'Pengadaan Digital Vernier Caliper Mitutoyo 0-300mm IP67 dan Inside Micrometer Tubular.',
                    'items' => [
                        ['nama' => 'Mitutoyo Digital Caliper 0-300mm IP67 (500-754-20)', 'part_number' => 'MTY-500-754', 'jumlah' => 2, 'satuan' => 'Unit', 'harga_satuan' => 4650000, 'spesifikasi' => 'Tahan air dan cairan pendingin coolant workshop dengan data output'],
                        ['nama' => 'Mitutoyo Inside Micrometer Tubular 50-300mm (137-205)', 'part_number' => 'MTY-137-205', 'jumlah' => 1, 'satuan' => 'Set',  'harga_satuan' => 6850000, 'spesifikasi' => 'Batang ekstensi presisi untuk pengukuran diameter dalam pipa bor'],
                    ],
                ],
                [
                    'applicant_name' => 'Faisal Akbar',
                    'applicant_nip'  => 'BMS-QC-0051',
                    'description'    => 'Pengadaan Portable Hardness Tester Equotip Leeb D-Probe Replacement & Test Block.',
                    'items' => [
                        ['nama' => 'Equotip Leeb Impact Device D-Probe Replacement', 'part_number' => 'EQP-PRB-LEEB-D', 'jumlah' => 1, 'satuan' => 'Unit', 'harga_satuan' => 1850000, 'spesifikasi' => 'Ujung bola karbida tungsten untuk uji kekerasan baja 4145H Mod'],
                        ['nama' => 'Master Hardness Test Reference Block 780 ± 20 HLD', 'part_number' => 'EQP-BLK-780HLD',  'jumlah' => 1, 'satuan' => 'Set',  'harga_satuan' => 5450000,  'spesifikasi' => 'Sertifikat kalibrasi resmi Proceq Swiss'],
                    ],
                ],
                [
                    'applicant_name' => 'Maya Indriyani, S.T.',
                    'applicant_nip'  => 'BMS-QA-0019',
                    'description'    => 'Pengadaan Diamond Stylus Mitutoyo Surftest SJ-210 untuk pengukuran kehalusan permukaan thread.',
                    'items' => [
                        ['nama' => 'Mitutoyo Surftest SJ-210 Detector Stylus 2µm', 'part_number' => 'MTY-SJ-STY-2U', 'jumlah' => 2, 'satuan' => 'Pcs', 'harga_satuan' => 4200000, 'spesifikasi' => 'Diamond tip radius 2 micron untuk alat ukur kekasaran permukaan Ra/Rz'],
                    ],
                ],
            ],
        ];
    }
}
