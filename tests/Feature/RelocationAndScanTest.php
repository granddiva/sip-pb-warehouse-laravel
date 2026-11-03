<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Box;
use App\Models\Document;
use App\Models\Rack;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelocationAndScanTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::first();
    }

    public function test_scanner_resolve_document_by_nomor_pb(): void
    {
        $doc = Document::first();

        $response = $this->actingAs($this->user)
            ->getJson("/api/scan/resolve?code={$doc->nomor_pb}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type'    => 'document',
            ])
            ->assertJsonPath('data.id', $doc->id)
            ->assertJsonPath('data.nomor_pb', $doc->nomor_pb);
    }

    public function test_scanner_resolve_document_by_url(): void
    {
        $doc = Document::first();
        $url = "http://127.0.0.1:8000/documents/{$doc->id}";

        $response = $this->actingAs($this->user)
            ->getJson("/api/scan/resolve?code=" . urlencode($url));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type'    => 'document',
            ])
            ->assertJsonPath('data.id', $doc->id);
    }

    public function test_scanner_resolve_box_by_nomor_box(): void
    {
        $box = Box::where('nomor_box', 'Box 01')->first();

        $response = $this->actingAs($this->user)
            ->getJson("/api/scan/resolve?code=Box 01");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type'    => 'box',
                'action'  => 'inspect_box',
            ])
            ->assertJsonPath('data.nomor_box', 'Box 01');
    }

    public function test_scanner_returns_404_for_unknown_code(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/scan/resolve?code=UNKNOWN-CODE-99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_document_relocation_successfully_moves_documents_and_updates_logs(): void
    {
        $doc = Document::where('status', 'Terverifikasi & Diarsipkan')->first();
        $targetRack = Rack::where('kode_rak', 'Rak D')->first();
        $targetBox = $targetRack->boxes()->first();

        $this->assertNotEquals($targetBox->id, $doc->box_id);

        $response = $this->actingAs($this->user)->postJson('/api/documents/relocate', [
            'document_ids'   => [$doc->id],
            'target_rack_id' => $targetRack->id,
            'target_box_id'  => $targetBox->id,
            'alasan'         => 'Reorganisasi ordner tahun buku 2026',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $doc->refresh();
        $this->assertEquals($targetBox->id, $doc->box_id);
        $this->assertEquals($targetRack->kode_rak, $doc->lokasi_rak);
        $this->assertEquals($targetBox->nomor_box, $doc->nomor_box);
        $this->assertStringContainsString('Reorganisasi ordner tahun buku 2026', $doc->catatan_arsip);

        // Verify ActivityLog was created
        $log = ActivityLog::where('activity_type', 'document_relocate')->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString($targetBox->nomor_box, $log->description);
    }

    public function test_document_relocation_fails_if_target_box_is_locked(): void
    {
        $doc = Document::where('status', 'Terverifikasi & Diarsipkan')->first();
        $targetRack = Rack::where('kode_rak', 'Rak B')->first();
        $targetBox = $targetRack->boxes()->first();

        // Lock the box
        $targetBox->update(['status' => 'Penuh (Terkunci)']);

        $response = $this->actingAs($this->user)->postJson('/api/documents/relocate', [
            'document_ids'   => [$doc->id],
            'target_rack_id' => $targetRack->id,
            'target_box_id'  => $targetBox->id,
            'alasan'         => 'Percobaan relokasi ke box terkunci',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_document_relocation_fails_if_document_is_currently_borrowed(): void
    {
        $doc = Document::where('status', 'Dokumen Dipinjam')->first();
        if (!$doc) {
            $doc = Document::where('status', 'Terverifikasi & Diarsipkan')->first();
            $doc->update(['status' => 'Dokumen Dipinjam']);
        }

        $targetRack = Rack::first();
        $targetBox = $targetRack->boxes()->first();

        $response = $this->actingAs($this->user)->postJson('/api/documents/relocate', [
            'document_ids'   => [$doc->id],
            'target_rack_id' => $targetRack->id,
            'target_box_id'  => $targetBox->id,
            'alasan'         => 'Percobaan memindahkan berkas yang sedang dipinjam',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_document_relocation_fails_if_capacity_is_exceeded(): void
    {
        $doc = Document::where('status', 'Terverifikasi & Diarsipkan')->first();
        $targetBox = Box::where('id', '!=', $doc->box_id)->first();
        $targetRack = $targetBox->rack;

        // Simulate full box by setting kapasitas_maksimal to current document count
        $targetBox->update(['kapasitas_maksimal' => $targetBox->documents()->count()]);

        $response = $this->actingAs($this->user)->postJson('/api/documents/relocate', [
            'document_ids'   => [$doc->id],
            'target_rack_id' => $targetRack->id,
            'target_box_id'  => $targetBox->id,
            'alasan'         => 'Percobaan memindahkan ke box melebihi kapasitas',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
