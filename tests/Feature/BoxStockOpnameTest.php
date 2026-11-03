<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Box;
use App\Models\Document;
use App\Models\Rack;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoxStockOpnameTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('username', 'admin')->first() ?? User::factory()->create();
    }

    public function test_box_schema_has_last_audited_at_and_user_relationship(): void
    {
        $box = Box::first();
        $this->assertNotNull($box);

        $box->update([
            'last_audited_at'         => now(),
            'last_audited_by_user_id' => $this->user->id,
        ]);

        $box->refresh();
        $this->assertNotNull($box->last_audited_at);
        $this->assertEquals($this->user->id, $box->last_audited_by_user_id);
        $this->assertEquals($this->user->id, $box->lastAuditedByUser->id);
        $this->assertNotNull($box->last_audited_formatted);
        $this->assertTrue($box->is_recently_audited);
    }

    public function test_commit_audit_successfully_updates_box_and_creates_activity_log(): void
    {
        $box = Box::with('documents')->first();
        $this->assertNotNull($box);

        $expectedDocs = $box->documents->pluck('nomor_pb')->toArray();
        $matchedPb = $expectedDocs[0] ?? 'PB/TEST/001';
        $missingPb = $expectedDocs[1] ?? 'PB/TEST/002';
        $misplacedPb = 'PB/BMS-WH/2026/09/FOREIGN';

        $response = $this->actingAs($this->user)->postJson("/api/boxes/{$box->id}/commit-audit", [
            'matched_pbs'   => [$matchedPb],
            'missing_pbs'   => [$missingPb],
            'misplaced_pbs' => [$misplacedPb],
            'catatan'       => 'Pemeriksaan fisik kuartal 4 berjalan lancar',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('box.id', $box->id)
            ->assertJsonPath('box.last_audited_by', $this->user->name);

        $box->refresh();
        $this->assertNotNull($box->last_audited_at);
        $this->assertEquals($this->user->id, $box->last_audited_by_user_id);

        // Verify ActivityLog was recorded
        $log = ActivityLog::where('activity_type', 'stock_opname')
            ->where('subject_type', Box::class)
            ->where('subject_id', $box->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertStringContainsString('Stock Opname fisik', $log->description);
        $this->assertEquals(1, $log->properties['matched_count']);
        $this->assertEquals(1, $log->properties['missing_count']);
        $this->assertEquals(1, $log->properties['misplaced_count']);
        $this->assertEquals('Pemeriksaan fisik kuartal 4 berjalan lancar', $log->properties['catatan']);
    }

    public function test_rack_controller_returns_audit_metadata_in_index_and_box_documents(): void
    {
        $box = Box::first();
        $box->update([
            'last_audited_at'         => now(),
            'last_audited_by_user_id' => $this->user->id,
        ]);

        // 1. Check GET /api/racks
        $racksResponse = $this->actingAs($this->user)->getJson('/api/racks');
        $racksResponse->assertStatus(200);

        $racksData = $racksResponse->json();
        $foundBox = null;
        foreach ($racksData as $r) {
            foreach ($r['boxes'] as $b) {
                if ($b['id'] === $box->id) {
                    $foundBox = $b;
                    break 2;
                }
            }
        }

        $this->assertNotNull($foundBox);
        $this->assertNotNull($foundBox['last_audited_at']);
        $this->assertNotNull($foundBox['last_audited_formatted']);
        $this->assertEquals($this->user->name, $foundBox['last_audited_by']);

        // 2. Check GET /api/boxes/{box}/documents
        $boxDocResponse = $this->actingAs($this->user)->getJson("/api/boxes/{$box->id}/documents");
        $boxDocResponse->assertStatus(200)
            ->assertJsonPath('box.id', $box->id)
            ->assertJsonPath('box.last_audited_by', $this->user->name);
        $this->assertNotNull($boxDocResponse->json('box.last_audited_formatted'));
    }

    public function test_scanner_resolve_can_resolve_misplaced_document_for_reconciliation(): void
    {
        $doc = Document::first();
        $this->assertNotNull($doc);

        $response = $this->actingAs($this->user)->getJson("/api/scan/resolve?code={$doc->nomor_pb}");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type'    => 'document',
            ])
            ->assertJsonPath('data.nomor_pb', $doc->nomor_pb);
    }
}
