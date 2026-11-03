<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Document;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_qrcodes_batch_endpoint()
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'name'     => 'Test User',
            'role'     => 'Admin Warehouse',
        ]);

        $rack = Rack::create([
            'kode_rak'    => 'Rak A',
            'nama_rak'    => 'Rak A Test',
            'zona_lokasi' => 'Zona 1',
            'warna_label' => '#0284c7',
            'baris'       => 2,
            'kolom'       => 2,
            'kapasitas_maksimal' => 40,
        ]);

        $box = Box::create([
            'rack_id'            => $rack->id,
            'nomor_box'          => 'Box 01',
            'baris_ke'           => 1,
            'kolom_ke'           => 1,
            'kapasitas_maksimal' => 20,
            'status'             => 'Tersedia',
        ]);

        $response = $this->actingAs($user)->getJson('/api/qrcodes/batch?type=boxes');
        $response->assertOk();

        // Seed 50 documents to test batch generation
        for ($i = 1; $i <= 50; $i++) {
            Document::create([
                'user_id'       => $user->id,
                'box_id'        => $box->id,
                'nomor_pb'      => "PB/TEST/2026/01/{$i}",
                'tanggal_pb'    => '2026-01-10',
                'bagian'        => 'Workshop Machining',
                'nama_pemohon'  => "User {$i}",
                'status'        => 'Terverifikasi & Diarsipkan',
            ]);
        }

        $responseDoc = $this->actingAs($user)->getJson('/api/qrcodes/batch?type=documents');
        if ($responseDoc->status() !== 200) {
            dump($responseDoc->json());
        }
        $responseDoc->assertOk();
    }
}
