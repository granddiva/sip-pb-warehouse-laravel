<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Document;
use App\Models\Rack;
use App\Models\User;
use App\Models\BorrowingLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $requesterMch;
    protected User $requesterOps;
    protected User $auditorUser;
    protected Rack $rack;
    protected Box $box;
    protected Document $mchDocument;
    protected Document $opsDocument;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->adminUser = User::create([
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'name'     => 'Admin Warehouse Test',
            'role'     => 'Admin Warehouse',
            'divisi'   => 'Warehouse & Logistik',
        ]);

        $this->requesterMch = User::create([
            'username' => 'requester_mch_test',
            'password' => bcrypt('password'),
            'name'     => 'Pemohon Machining',
            'role'     => 'requester_division',
            'divisi'   => 'Workshop Machining & Threading',
        ]);

        $this->requesterOps = User::create([
            'username' => 'requester_ops_test',
            'password' => bcrypt('password'),
            'name'     => 'Pemohon Ops',
            'role'     => 'requester_division',
            'divisi'   => 'Operasional Lapangan',
        ]);

        $this->auditorUser = User::create([
            'username' => 'auditor_test',
            'password' => bcrypt('password'),
            'name'     => 'Auditor SKK Migas Test',
            'role'     => 'Auditor',
            'divisi'   => 'Audit & Compliance',
        ]);

        $this->rack = Rack::create([
            'kode_rak'           => 'Rak RBAC',
            'nama_rak'           => 'Rak RBAC Testing',
            'zona_lokasi'        => 'Zona Test',
            'warna_label'        => '#0ea5e9',
            'baris'              => 2,
            'kolom'              => 2,
            'kapasitas_maksimal' => 40,
        ]);

        $this->box = Box::create([
            'rack_id'            => $this->rack->id,
            'nomor_box'          => 'Box RBAC-01',
            'baris_ke'           => 1,
            'kolom_ke'           => 1,
            'kapasitas_maksimal' => 20,
            'status'             => 'Tersedia',
        ]);

        $this->mchDocument = Document::create([
            'user_id'       => $this->requesterMch->id,
            'nomor_pb'      => 'PB/MCH/2026/01/001',
            'tanggal_pb'    => '2026-01-10',
            'bagian'        => 'Workshop Machining & Threading',
            'nama_pemohon'  => 'Budi MCH',
            'status'        => 'Menunggu Arsip Fisik',
            'lokasi_rak'    => 'Rak RBAC',
            'nomor_box'     => 'Box RBAC-01',
            'box_id'        => $this->box->id,
        ]);

        $this->opsDocument = Document::create([
            'user_id'       => $this->requesterOps->id,
            'nomor_pb'      => 'PB/OPS/2026/01/002',
            'tanggal_pb'    => '2026-01-12',
            'bagian'        => 'Operasional Lapangan',
            'nama_pemohon'  => 'Rian OPS',
            'status'        => 'Terverifikasi & Diarsipkan',
            'lokasi_rak'    => 'Rak RBAC',
            'nomor_box'     => 'Box RBAC-01',
            'box_id'        => $this->box->id,
        ]);
    }

    public function test_user_model_role_helpers_and_computed_attributes()
    {
        $this->assertTrue($this->adminUser->is_admin_warehouse);
        $this->assertTrue($this->adminUser->can_manage_warehouse);
        $this->assertFalse($this->adminUser->is_auditor);
        $this->assertFalse($this->adminUser->is_requester_division);

        $this->assertTrue($this->auditorUser->is_auditor);
        $this->assertFalse($this->auditorUser->can_manage_warehouse);
        $this->assertFalse($this->auditorUser->canMutate());

        $this->assertTrue($this->requesterMch->is_requester_division);
        $this->assertFalse($this->requesterMch->can_manage_warehouse);

        $this->assertTrue($this->requesterMch->canEditDocument($this->mchDocument));
        $this->assertFalse($this->requesterMch->canEditDocument($this->opsDocument));
        $this->assertFalse($this->auditorUser->canEditDocument($this->mchDocument));
    }

    public function test_auditor_has_read_only_access_and_blocked_from_all_mutations()
    {
        $this->actingAs($this->auditorUser);

        // 1. Can READ documents and racks
        $this->getJson('/api/documents')->assertOk();
        $this->getJson('/api/documents/' . $this->mchDocument->id)->assertOk();
        $this->getJson('/api/racks')->assertOk();
        $this->getJson('/api/borrowings')->assertOk();
        $this->getJson('/api/documents/export')->assertOk();

        // 2. Blocked from creating PB (403)
        $this->postJson('/api/documents', [
            'nomor_pb'     => 'PB/AUD/TEST/001',
            'tanggal_pb'   => '2026-01-15',
            'bagian'       => 'Audit & Compliance',
            'nama_pemohon' => 'Auditor X',
            'status'       => 'Menunggu Arsip Fisik',
        ])->assertStatus(403);

        // 3. Blocked from updating PB (403)
        $this->putJson('/api/documents/' . $this->mchDocument->id, [
            'nomor_pb'     => $this->mchDocument->nomor_pb,
            'tanggal_pb'   => '2026-01-10',
            'bagian'       => $this->mchDocument->bagian,
            'nama_pemohon' => 'Auditor Edit',
        ])->assertStatus(403);

        // 4. Blocked from deleting PB (403)
        $this->deleteJson('/api/documents/' . $this->mchDocument->id)->assertStatus(403);

        // 5. Blocked from verifying physical PB (403)
        $this->postJson('/api/documents/' . $this->mchDocument->id . '/verify-physical', [
            'box_id'               => $this->box->id,
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
        ])->assertStatus(403);

        // 6. Blocked from rejecting physical PB (403)
        $this->postJson('/api/documents/' . $this->mchDocument->id . '/reject-physical', [
            'alasan_penolakan' => 'Revisi oleh auditor',
        ])->assertStatus(403);

        // 7. Blocked from relocation (403)
        $this->postJson('/api/documents/relocate', [
            'document_ids'   => [$this->mchDocument->id],
            'target_rack_id' => $this->rack->id,
            'target_box_id'  => $this->box->id,
            'alasan'         => 'Audit relocate',
        ])->assertStatus(403);

        // 8. Blocked from borrowing (403)
        $this->postJson('/api/borrowings', [
            'document_id'         => $this->opsDocument->id,
            'nama_peminjam'       => 'Peminjam X',
            'departemen_peminjam' => 'Finance',
            'keperluan'           => 'Audit Keuangan',
            'tanggal_pinjam'      => '2026-01-15',
            'batas_kembali'       => '2026-01-20',
        ])->assertStatus(403);

        // 9. Blocked from locking box (403)
        $this->postJson('/api/boxes/' . $this->box->id . '/toggle-lock')->assertStatus(403);

        // 10. Blocked from uploading attachment (403)
        $fakeFile = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $this->postJson('/api/documents/' . $this->mchDocument->id . '/attachments', [
            'jenis_dokumen' => 'Form PB Scan Asli',
            'file'          => $fakeFile,
        ])->assertStatus(403);
    }

    public function test_requester_division_can_only_manage_own_division_pbs()
    {
        $this->actingAs($this->requesterMch);

        // 1. Can create PB for their division
        $createRes = $this->postJson('/api/documents', [
            'nomor_pb'     => 'PB/MCH/2026/01/099',
            'tanggal_pb'   => '2026-01-15',
            'bagian'       => 'Workshop Machining & Threading',
            'nama_pemohon' => 'Budi MCH',
            'status'       => 'Menunggu Arsip Fisik',
        ]);
        $createRes->assertOk();
        $this->assertDatabaseHas('documents', ['nomor_pb' => 'PB/MCH/2026/01/099']);

        // 2. Can update document from their own division
        $this->putJson('/api/documents/' . $this->mchDocument->id, [
            'nomor_pb'     => $this->mchDocument->nomor_pb,
            'tanggal_pb'   => '2026-01-10',
            'bagian'       => 'Workshop Machining & Threading',
            'nama_pemohon' => 'Budi MCH Updated',
        ])->assertOk();

        // 3. CANNOT update document of another division (403)
        $this->putJson('/api/documents/' . $this->opsDocument->id, [
            'nomor_pb'     => $this->opsDocument->nomor_pb,
            'tanggal_pb'   => '2026-01-12',
            'bagian'       => 'Operasional Lapangan',
            'nama_pemohon' => 'Hacker Budi',
        ])->assertStatus(403);

        // 4. CANNOT delete PB (403)
        $this->deleteJson('/api/documents/' . $this->mchDocument->id)->assertStatus(403);

        // 5. CANNOT verify physical PB (403)
        $this->postJson('/api/documents/' . $this->mchDocument->id . '/verify-physical', [
            'box_id'               => $this->box->id,
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
        ])->assertStatus(403);

        // 6. CANNOT reject physical PB (403)
        $this->postJson('/api/documents/' . $this->mchDocument->id . '/reject-physical', [
            'alasan_penolakan' => 'Revisi oleh requester',
        ])->assertStatus(403);

        // 7. CANNOT relocate document (403)
        $this->postJson('/api/documents/relocate', [
            'document_ids'   => [$this->mchDocument->id],
            'target_rack_id' => $this->rack->id,
            'target_box_id'  => $this->box->id,
            'alasan'         => 'Requester relocate',
        ])->assertStatus(403);

        // 8. CANNOT checkout borrowing (403)
        $this->postJson('/api/borrowings', [
            'document_id'         => $this->opsDocument->id,
            'nama_peminjam'       => 'Budi MCH',
            'departemen_peminjam' => 'Workshop Machining & Threading',
            'keperluan'           => 'Pinjam berkas',
            'tanggal_pinjam'      => '2026-01-15',
            'batas_kembali'       => '2026-01-20',
        ])->assertStatus(403);

        // 9. Can upload attachment to own division PB
        $fakeFile = UploadedFile::fake()->create('machining_po.pdf', 100, 'application/pdf');
        $this->postJson('/api/documents/' . $this->mchDocument->id . '/attachments', [
            'jenis_dokumen' => 'Purchase Order (PO)',
            'file'          => $fakeFile,
        ])->assertOk();

        // 10. CANNOT upload attachment to another division PB (403)
        $fakeFile2 = UploadedFile::fake()->create('ops_po.pdf', 100, 'application/pdf');
        $this->postJson('/api/documents/' . $this->opsDocument->id . '/attachments', [
            'jenis_dokumen' => 'Purchase Order (PO)',
            'file'          => $fakeFile2,
        ])->assertStatus(403);
    }

    public function test_admin_warehouse_has_full_mutation_access()
    {
        $this->actingAs($this->adminUser);

        // 1. Can verify physical PB
        $verifyRes = $this->postJson('/api/documents/' . $this->mchDocument->id . '/verify-physical', [
            'box_id'               => $this->box->id,
            'kondisi_berkas_fisik' => 'Asli & Cap Basah Lengkap',
            'cek_tanda_tangan'     => true,
            'cek_lampiran_lengkap' => true,
            'catatan_verifikasi'   => 'Hardcopy diverifikasi lengkap',
        ]);
        $verifyRes->assertOk();
        $this->assertEquals('Terverifikasi & Diarsipkan', $this->mchDocument->fresh()->status);

        // 2. Can checkout borrowing
        $borrowRes = $this->postJson('/api/borrowings', [
            'document_id'         => $this->opsDocument->id,
            'nama_peminjam'       => 'Peminjam Lapangan',
            'departemen_peminjam' => 'Operasional',
            'keperluan'           => 'Audit Lapangan',
            'tanggal_pinjam'      => '2026-01-15',
            'batas_kembali'       => '2026-01-20',
        ]);
        $borrowRes->assertOk();
        $borrowingId = $borrowRes->json('borrowing.id');

        // 3. Can return borrowed document
        $returnRes = $this->postJson('/api/borrowings/' . $borrowingId . '/return', [
            'tanggal_kembali'     => '2026-01-18',
            'is_form_pb_lengkap'  => true,
            'is_lampiran_lengkap' => true,
            'kondisi_fisik'       => 'Baik & Utuh',
        ]);
        $returnRes->assertOk();

        // 4. Can toggle box lock
        $this->postJson('/api/boxes/' . $this->box->id . '/toggle-lock')->assertOk();

        // 5. Can commit stock opname
        $this->postJson('/api/boxes/' . $this->box->id . '/commit-audit', [
            'matched_pbs'   => [$this->opsDocument->nomor_pb],
            'missing_pbs'   => [],
            'misplaced_pbs' => [],
            'catatan'       => 'Audit fisik sukses',
        ])->assertOk();
    }
}
