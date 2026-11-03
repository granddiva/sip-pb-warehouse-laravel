<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Box;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\Rack;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackendArchitectureHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('username', 'admin')->first() ?? User::factory()->create();
    }

    /**
     * Requirement 1: Normalisasi Data Lokasi Rak & Box secara dinamis
     */
    public function test_document_locations_dynamically_resolved_from_box_and_rack_relationship(): void
    {
        $rack = Rack::where('kode_rak', 'Rak A')->first();
        $box = $rack->boxes()->first();

        $doc = Document::create([
            'user_id'      => $this->user->id,
            'box_id'       => $box->id,
            'nomor_pb'     => 'PB/BMS-WH/2026/11/NORM01',
            'tanggal_pb'   => '2026-11-03',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Udin Syarif',
            'status'       => 'Terverifikasi & Diarsipkan',
            'lokasi_rak'   => 'STALE_CACHE_RAK', // intentional stale cache to verify dynamic Eloquent resolution
            'nomor_box'    => 'STALE_CACHE_BOX',
        ]);

        // Access via show API
        $response = $this->actingAs($this->user)->getJson("/api/documents/{$doc->id}");
        $response->assertStatus(200)
            ->assertJsonPath('lokasi_rak', $rack->kode_rak)
            ->assertJsonPath('nomor_box', $box->nomor_box);

        // Access via index API
        $listResponse = $this->actingAs($this->user)->getJson("/api/documents?search=NORM01");
        $listResponse->assertStatus(200)
            ->assertJsonPath('data.0.lokasi_rak', $rack->kode_rak)
            ->assertJsonPath('data.0.nomor_box', $box->nomor_box);
    }

    /**
     * Requirement 2: Pessimistic Locking & Auto-Lock Box saat Kuota Penuh (verifyPhysical)
     */
    public function test_verify_physical_pessimistic_lock_and_box_auto_lock_when_capacity_reached(): void
    {
        $rack = Rack::first();
        $testBox = Box::create([
            'rack_id'            => $rack->id,
            'nomor_box'          => 'Box Test Max 1',
            'baris_ke'           => 99,
            'kolom_ke'           => 1,
            'kapasitas_maksimal' => 1,
            'status'             => 'Tersedia',
        ]);

        $pendingDoc = Document::create([
            'user_id'      => $this->user->id,
            'nomor_pb'     => 'PB/BMS-WH/2026/11/PENDING01',
            'tanggal_pb'   => '2026-11-03',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Andi Susanto',
            'status'       => 'Menunggu Arsip Fisik',
        ]);

        $this->assertEquals('Tersedia', $testBox->status);

        // Verify document into testBox
        $response = $this->actingAs($this->user)->postJson("/api/documents/{$pendingDoc->id}/verify-physical", [
            'box_id'               => $testBox->id,
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
            'catatan_verifikasi'   => 'Verifikasi masuk box kapasitas 1',
        ]);

        $response->assertStatus(200);

        // Box must now automatically transition to "Penuh (Terkunci)"
        $testBox->refresh();
        $this->assertEquals('Penuh (Terkunci)', $testBox->status);

        // Second verification to the same box must be rejected
        $secondDoc = Document::create([
            'user_id'      => $this->user->id,
            'nomor_pb'     => 'PB/BMS-WH/2026/11/PENDING02',
            'tanggal_pb'   => '2026-11-03',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Budi Santoso',
            'status'       => 'Menunggu Arsip Fisik',
        ]);

        $rejectResponse = $this->actingAs($this->user)->postJson("/api/documents/{$secondDoc->id}/verify-physical", [
            'box_id'               => $testBox->id,
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
        ]);

        $rejectResponse->assertStatus(422);
    }

    /**
     * Requirement 2: Relokasi Auto-Locks target Box saat kapasitas terpenuhi
     */
    public function test_relocation_auto_locks_target_box_when_reaches_capacity(): void
    {
        $rack = Rack::first();
        $targetBox = Box::create([
            'rack_id'            => $rack->id,
            'nomor_box'          => 'Box Test Reloc Max 1',
            'baris_ke'           => 98,
            'kolom_ke'           => 1,
            'kapasitas_maksimal' => 1,
            'status'             => 'Tersedia',
        ]);

        $docToRelocate = Document::where('status', 'Terverifikasi & Diarsipkan')->first();

        $response = $this->actingAs($this->user)->postJson('/api/documents/relocate', [
            'document_ids'   => [$docToRelocate->id],
            'target_rack_id' => $rack->id,
            'target_box_id'  => $targetBox->id,
            'alasan'         => 'Uji kapasitas box relokasi',
        ]);

        $response->assertStatus(200);

        $targetBox->refresh();
        $this->assertEquals('Penuh (Terkunci)', $targetBox->status);
    }

    /**
     * Requirement 3: Keamanan File Lampiran (Private Storage & Auth Protected Endpoints)
     */
    public function test_private_attachment_storage_and_authenticated_preview_and_download(): void
    {
        Storage::fake('local');

        $doc = Document::first();
        $fakeFile = UploadedFile::fake()->create('sop_material.pdf', 250, 'application/pdf');

        // Store attachment via API
        $uploadResponse = $this->actingAs($this->user)->postJson("/api/documents/{$doc->id}/attachments", [
            'jenis_dokumen' => 'Surat Pengantar Vendor',
            'file'          => $fakeFile,
        ]);

        $uploadResponse->assertStatus(200);
        $attachmentId = $uploadResponse->json('attachment.id');
        $this->assertNotNull($attachmentId);

        $attachment = DocumentAttachment::find($attachmentId);
        $this->assertNotNull($attachment);

        // Assert file exists on private local disk
        Storage::disk('local')->assertExists($attachment->file_path);

        // 1. Unauthenticated request to preview/download MUST be rejected (401)
        auth()->logout();
        $unauthPreview = $this->getJson("/api/attachments/{$attachmentId}/preview");
        $unauthPreview->assertStatus(401);

        $unauthDownload = $this->getJson("/api/attachments/{$attachmentId}/download");
        $unauthDownload->assertStatus(401);

        // 2. Authenticated user can preview inline
        $authPreview = $this->actingAs($this->user)->get("/api/attachments/{$attachmentId}/preview");
        $authPreview->assertStatus(200);
        $authPreview->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $authPreview->headers->get('Content-Disposition') ?? '');

        // 3. Authenticated user can download
        $authDownload = $this->actingAs($this->user)->get("/api/attachments/{$attachmentId}/download");
        $authDownload->assertStatus(200);
        $this->assertStringContainsString('attachment', $authDownload->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Requirement 4: Standar Audit ISO dengan subject dan properties (old_values & new_values)
     */
    public function test_activity_logs_stores_subject_and_old_new_values_for_audit(): void
    {
        // 1. Verify Physical Audit Log
        $pendingDoc = Document::create([
            'user_id'      => $this->user->id,
            'nomor_pb'     => 'PB/BMS-WH/2026/11/AUDIT01',
            'tanggal_pb'   => '2026-11-03',
            'bagian'       => 'HSE & K3L',
            'nama_pemohon' => 'Kurniawan',
            'status'       => 'Menunggu Arsip Fisik',
            'lokasi_rak'   => 'Rak A',
            'nomor_box'    => 'Box 02',
        ]);

        $this->actingAs($this->user)->postJson("/api/documents/{$pendingDoc->id}/verify-physical", [
            'lokasi_rak'           => 'Rak A',
            'nomor_box'            => 'Box 02',
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
            'catatan_verifikasi'   => 'Audit checklist OK',
        ])->assertStatus(200);

        $verifyLog = ActivityLog::where('activity_type', 'verify_physical')
            ->where('subject_type', Document::class)
            ->where('subject_id', $pendingDoc->id)
            ->latest()
            ->first();

        $this->assertNotNull($verifyLog);
        $this->assertEquals('Menunggu Arsip Fisik', $verifyLog->properties['old_values']['status']);
        $this->assertEquals('Terverifikasi & Diarsipkan', $verifyLog->properties['new_values']['status']);
        $this->assertTrue($verifyLog->properties['checklist']['cek_tanda_tangan']);

        // 2. Borrow Audit Log
        $this->actingAs($this->user)->postJson('/api/borrowings', [
            'document_id'         => $pendingDoc->id,
            'nama_peminjam'       => 'Dedi Mulyadi',
            'departemen_peminjam' => 'Operasional Lapangan',
            'keperluan'           => 'Audit Rig Lapangan',
            'tanggal_pinjam'      => '2026-11-03',
            'batas_kembali'       => '2026-11-07',
        ])->assertStatus(200);

        $borrowLog = ActivityLog::where('activity_type', 'borrow')
            ->where('subject_type', Document::class)
            ->where('subject_id', $pendingDoc->id)
            ->latest()
            ->first();

        $this->assertNotNull($borrowLog);
        $this->assertEquals('Terverifikasi & Diarsipkan', $borrowLog->properties['old_values']['status']);
        $this->assertEquals('Dokumen Dipinjam', $borrowLog->properties['new_values']['status']);

        // 3. Return Audit Log
        $activeBorrowing = BorrowingLog::where('document_id', $pendingDoc->id)->first();
        $this->actingAs($this->user)->postJson("/api/borrowings/{$activeBorrowing->id}/return", [
            'tanggal_kembali'     => '2026-11-05',
            'is_form_pb_lengkap'  => true,
            'is_lampiran_lengkap' => true,
            'kondisi_fisik'       => 'Baik & Utuh',
        ])->assertStatus(200);

        $returnLog = ActivityLog::where('activity_type', 'return')
            ->where('subject_type', Document::class)
            ->where('subject_id', $pendingDoc->id)
            ->latest()
            ->first();

        $this->assertNotNull($returnLog);
        $this->assertEquals('Dokumen Dipinjam', $returnLog->properties['old_values']['document_status']);
        $this->assertEquals('Terverifikasi & Diarsipkan', $returnLog->properties['new_values']['document_status']);
        $this->assertEquals('Baik & Utuh', $returnLog->properties['new_values']['kondisi_fisik']);

        // 4. Document Relocate Audit Log
        $targetRack = Rack::where('kode_rak', 'Rak C')->first();
        $targetBox = $targetRack->boxes()->first();

        $this->actingAs($this->user)->postJson('/api/documents/relocate', [
            'document_ids'   => [$pendingDoc->id],
            'target_rack_id' => $targetRack->id,
            'target_box_id'  => $targetBox->id,
            'alasan'         => 'Pindah berkas audit ke rak C',
        ])->assertStatus(200);

        $relocateLog = ActivityLog::where('activity_type', 'document_relocate')
            ->where('subject_type', Document::class)
            ->where('subject_id', $pendingDoc->id)
            ->latest()
            ->first();

        $this->assertNotNull($relocateLog);
        $this->assertEquals('Pindah berkas audit ke rak C', $relocateLog->properties['alasan']);
        $this->assertEquals($targetRack->kode_rak, $relocateLog->properties['new_values']['lokasi_rak']);
    }

    /**
     * Requirement: Penolakan / Revisi Berkas Fisik State Machine & Activity Log
     */
    public function test_reject_physical_transitions_status_and_logs_audit_trail(): void
    {
        $pendingDoc = Document::create([
            'user_id'      => $this->user->id,
            'nomor_pb'     => 'PB/BMS-WH/2026/11/REJECT01',
            'tanggal_pb'   => '2026-11-03',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Bambang Sudirman',
            'status'       => 'Menunggu Arsip Fisik',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/documents/{$pendingDoc->id}/reject-physical", [
            'alasan_penolakan' => 'Tanda tangan/cap basah belum lengkap; Lampiran PO/DO/BAST kurang',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('document.status', 'Perlu Revisi Fisik');

        $pendingDoc->refresh();
        $this->assertEquals('Perlu Revisi Fisik', $pendingDoc->status);
        $this->assertEquals('Tanda tangan/cap basah belum lengkap; Lampiran PO/DO/BAST kurang', $pendingDoc->catatan_verifikasi);
        $this->assertStringContainsString('Revisi Fisik', $pendingDoc->catatan_arsip);

        // Verify Activity Log was recorded
        $log = ActivityLog::where('activity_type', 'reject_physical')
            ->where('subject_type', Document::class)
            ->where('subject_id', $pendingDoc->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Menunggu Arsip Fisik', $log->properties['old_values']['status']);
        $this->assertEquals('Perlu Revisi Fisik', $log->properties['new_values']['status']);
        $this->assertEquals('Tanda tangan/cap basah belum lengkap; Lampiran PO/DO/BAST kurang', $log->properties['alasan_penolakan']);
    }

    public function test_reject_physical_fails_for_non_pending_documents(): void
    {
        $verifiedDoc = Document::where('status', 'Terverifikasi & Diarsipkan')->first();

        $response = $this->actingAs($this->user)->postJson("/api/documents/{$verifiedDoc->id}/reject-physical", [
            'alasan_penolakan' => 'Coba tolak dokumen yang sudah terverifikasi',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_document_with_status_perlu_revisi_fisik_can_be_edited_and_resubmitted(): void
    {
        $doc = Document::create([
            'user_id'            => $this->user->id,
            'nomor_pb'           => 'PB/BMS-WH/2026/11/REVISI02',
            'tanggal_pb'         => '2026-11-03',
            'bagian'             => 'Warehouse & Logistik',
            'nama_pemohon'       => 'Hendra Wijaya',
            'status'             => 'Perlu Revisi Fisik',
            'catatan_verifikasi' => 'Tanda tangan pemohon kurang',
        ]);

        // 1. Cannot be borrowed while in revision
        $borrowAttempt = $this->actingAs($this->user)->postJson('/api/borrowings', [
            'document_id'         => $doc->id,
            'nama_peminjam'       => 'Peminjam Gelap',
            'departemen_peminjam' => 'Operasional Lapangan',
            'keperluan'           => 'Uji coba pinjam',
            'tanggal_pinjam'      => '2026-11-03',
            'batas_kembali'       => '2026-11-05',
        ]);
        $borrowAttempt->assertStatus(422);

        // 2. Pemohon edits and resubmits the document
        $updateResponse = $this->actingAs($this->user)->putJson("/api/documents/{$doc->id}", [
            'nomor_pb'     => 'PB/BMS-WH/2026/11/REVISI02',
            'tanggal_pb'   => '2026-11-03',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Hendra Wijaya (Revisi Lengkap)',
            'status'       => 'Menunggu Arsip Fisik', // Resubmit
        ]);

        $updateResponse->assertStatus(200);

        $doc->refresh();
        $this->assertEquals('Menunggu Arsip Fisik', $doc->status);
        $this->assertEquals('Hendra Wijaya (Revisi Lengkap)', $doc->nama_pemohon);

        // 3. Warehouse verifies physical and shelves it to Rak A Box 01
        $rack = Rack::where('kode_rak', 'Rak A')->first();
        $box = $rack->boxes()->first();

        $verifyResponse = $this->actingAs($this->user)->postJson("/api/documents/{$doc->id}/verify-physical", [
            'box_id'               => $box->id,
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
            'catatan_verifikasi'   => 'Verifikasi ulang setelah revisi selesai',
        ]);

        $verifyResponse->assertStatus(200);

        $doc->refresh();
        $this->assertEquals('Terverifikasi & Diarsipkan', $doc->status);
    }
}
