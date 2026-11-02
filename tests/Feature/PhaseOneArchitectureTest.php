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
use Tests\TestCase;

class PhaseOneArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_racks_and_boxes_relationship_and_dynamic_capacity(): void
    {
        $rack = Rack::where('kode_rak', 'Rak A')->first();
        $this->assertNotNull($rack);
        $this->assertGreaterThan(0, $rack->boxes()->count());
        $this->assertGreaterThan(0, $rack->documents()->count());

        $this->assertIsInt($rack->persentase_kapasitas);
        $this->assertContains($rack->status_kapasitas, ['Tersedia', 'Sedang', 'Hampir Penuh']);

        $box = $rack->boxes()->first();
        $this->assertEquals($rack->id, $box->rack->id);
        $this->assertIsInt($box->sisa_kapasitas);
    }

    public function test_document_relationships_and_multi_attachments(): void
    {
        $doc = Document::where('nomor_pb', 'PB/BMS-WH/2026/09/001')->first();
        $this->assertNotNull($doc);
        $this->assertNotNull($doc->box);
        $this->assertNotNull($doc->user);
        $this->assertGreaterThan(0, $doc->items()->count());
        $this->assertGreaterThan(0, $doc->attachments()->count());

        $firstAttachment = $doc->attachments()->first();
        $this->assertTrue($firstAttachment->is_pdf);
    }

    public function test_borrowing_logs_and_overdue_detection(): void
    {
        $docOverdue = Document::where('nomor_pb', 'PB/BMS-MTN/2026/09/005')->first();
        $this->assertNotNull($docOverdue);
        $this->assertNotNull($docOverdue->activeBorrowing);

        $borrowing = $docOverdue->activeBorrowing;
        $this->assertEquals('Dipinjam', $borrowing->status_pinjam);
        $this->assertTrue($borrowing->is_overdue);
    }

    public function test_activity_logs_helper(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin);

        $log = ActivityLog::log('test_action', 'Test activity logging description');

        $this->assertDatabaseHas('activity_logs', [
            'id'            => $log->id,
            'user_id'       => $admin->id,
            'activity_type' => 'test_action',
        ]);
    }

    public function test_documents_export_csv(): void
    {
        $admin = User::where('username', 'admin')->first();
        $response = $this->actingAs($admin)->get('/api/documents/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('rekap_dokumen_pb', $response->headers->get('Content-Disposition'));
    }

    public function test_borrowings_export_csv(): void
    {
        $admin = User::where('username', 'admin')->first();
        $response = $this->actingAs($admin)->get('/api/borrowings/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('rekap_sirkulasi_peminjaman', $response->headers->get('Content-Disposition'));
    }

    public function test_document_status_state_machine_enforced(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Cannot store with invalid initial status
        $badResponse = $this->actingAs($admin)->postJson('/api/documents', [
            'nomor_pb'     => 'PB/TEST/2026/11/999',
            'tanggal_pb'   => '2026-11-02',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Budi Santoso',
            'status'       => 'Dokumen Dipinjam', // forbidden at creation
        ]);
        $badResponse->assertStatus(422);

        // 2. Valid initial creation
        $goodResponse = $this->actingAs($admin)->postJson('/api/documents', [
            'nomor_pb'     => 'PB/TEST/2026/11/999',
            'tanggal_pb'   => '2026-11-02',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Budi Santoso',
            'status'       => 'Terverifikasi & Diarsipkan',
            'lokasi_rak'   => 'Rak A',
            'nomor_box'    => 'Box 01',
        ]);
        $goodResponse->assertStatus(200);
        $docId = $goodResponse->json('document.id');

        // 3. Borrow transitions document status
        $borrowResponse = $this->actingAs($admin)->postJson('/api/borrowings', [
            'document_id'         => $docId,
            'nama_peminjam'       => 'Rian Pratama',
            'departemen_peminjam' => 'Workshop Machining & Threading',
            'keperluan'           => 'Pemeriksaan dimensi part',
            'tanggal_pinjam'      => '2026-11-02',
            'batas_kembali'       => '2026-11-05',
        ]);
        $borrowResponse->assertStatus(200);

        $doc = Document::find($docId);
        $this->assertEquals('Dokumen Dipinjam', $doc->status);

        // 4. Update cannot bypass Dipinjam
        $this->actingAs($admin)->putJson("/api/documents/{$docId}", [
            'nomor_pb'     => 'PB/TEST/2026/11/999',
            'tanggal_pb'   => '2026-11-02',
            'bagian'       => 'Warehouse & Logistik',
            'nama_pemohon' => 'Budi Santoso',
            'status'       => 'Terverifikasi & Diarsipkan', // attempts to overwrite loan
        ]);
        $doc->refresh();
        $this->assertEquals('Dokumen Dipinjam', $doc->status);
    }

    public function test_physical_verification_checklist_on_return(): void
    {
        $admin = User::where('username', 'admin')->first();
        $borrowing = BorrowingLog::where('status_pinjam', 'Dipinjam')->first();
        $this->assertNotNull($borrowing);

        $returnResponse = $this->actingAs($admin)->postJson("/api/borrowings/{$borrowing->id}/return", [
            'tanggal_kembali'     => '2026-11-02',
            'is_form_pb_lengkap'  => true,
            'is_lampiran_lengkap' => true,
            'kondisi_fisik'       => 'Baik & Utuh',
            'catatan_kembali'     => 'Berkas fisik diperiksa lengkap dan dikembalikan ke ordner.',
        ]);

        $returnResponse->assertStatus(200);

        $borrowing->refresh();
        $this->assertEquals('Dikembalikan', $borrowing->status_pinjam);
        $this->assertTrue($borrowing->is_form_pb_lengkap);
        $this->assertTrue($borrowing->is_lampiran_lengkap);
        $this->assertEquals('Baik & Utuh', $borrowing->kondisi_fisik);
        $this->assertEquals($admin->id, $borrowing->returned_to_user_id);

        $this->assertEquals('Terverifikasi & Diarsipkan', $borrowing->document->status);
    }
}
