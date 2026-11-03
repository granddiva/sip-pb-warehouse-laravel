<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function document(Document $document)
    {
        $document->load(['box.rack', 'items']);
        $url = url('/documents/' . $document->id);
        $qrSvg = $this->renderQrSvg($url, 120);

        return response()->json([
            'document' => [
                'id'         => $document->id,
                'nomor_pb'   => $document->nomor_pb,
                'bagian'     => $document->bagian,
                'pemohon'    => $document->nama_pemohon,
                'tanggal'    => $document->tanggal_indonesia,
                'lokasi_rak' => $document->lokasi_rak_formatted,
                'nomor_box'  => $document->nomor_box_formatted,
            ],
            'qr_svg'   => $qrSvg,
            'url'      => $url,
        ]);
    }

    public function box(Box $box)
    {
        $box->load(['rack', 'documents']);
        $url = url('/racks?box_id=' . $box->id);
        $qrSvg = $this->renderQrSvg($url, 130);

        return response()->json([
            'box' => [
                'id'                 => $box->id,
                'nomor_box'          => $box->nomor_box,
                'rack_kode'          => $box->rack?->kode_rak,
                'rack_nama'          => $box->rack?->nama_rak,
                'zona_lokasi'        => $box->rack?->zona_lokasi,
                'kapasitas_maksimal' => $box->kapasitas_maksimal,
                'total_dokumen'      => $box->total_dokumen,
            ],
            'qr_svg' => $qrSvg,
            'url'    => $url,
        ]);
    }

    public function batch(Request $request)
    {
        try {
            $type = $request->get('type', 'boxes');

            if ($type === 'documents') {
                $documents = Document::with('box.rack')->latest('tanggal_pb')->take(100)->get();
                $items = $documents->map(function ($doc) {
                    return [
                        'id'       => $doc->id,
                        'title'    => $doc->nomor_pb,
                        'subtitle' => $doc->bagian,
                        'detail_1' => 'Pemohon: ' . $doc->nama_pemohon,
                        'detail_2' => 'Lokasi: ' . ($doc->lokasi_rak_formatted . ' - ' . $doc->nomor_box_formatted),
                        'detail_3' => 'Tgl: ' . $doc->tanggal_indonesia,
                        'qr_svg'   => $this->renderQrSvg(url('/documents/' . $doc->id), 100),
                    ];
                });
            } else {
                $boxes = Box::with('rack')->orderBy('rack_id')->orderBy('nomor_box')->get();
                $items = $boxes->map(function ($box) {
                    return [
                        'id'       => $box->id,
                        'title'    => ($box->rack?->kode_rak ?? 'RAK') . ' / ' . $box->nomor_box,
                        'subtitle' => $box->rack?->nama_rak ?? 'Warehouse',
                        'detail_1' => 'Zona: ' . ($box->rack?->zona_lokasi ?? '-'),
                        'detail_2' => 'Kapasitas: ' . $box->total_dokumen . '/' . $box->kapasitas_maksimal . ' Ordner',
                        'detail_3' => 'Status: ' . $box->status,
                        'qr_svg'   => $this->renderQrSvg(url('/racks?box_id=' . $box->id), 100),
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'type'    => $type,
                'items'   => $items,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error generating QR batch: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat label QR: ' . $e->getMessage(),
                'items'   => [],
            ], 500);
        }
    }

    /**
     * Render SVG QR code with graceful fallback if SimpleSoftwareIO\QrCode fails.
     */
    private function renderQrSvg(string $url, int $size = 100): string
    {
        try {
            if (class_exists(QrCode::class)) {
                return (string) QrCode::size($size)->generate($url);
            }
        } catch (\Throwable $e) {
            Log::warning('QrCode facade execution failed, using SVG fallback: ' . $e->getMessage());
        }

        // Lightweight pure-SVG QR placeholder
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100" fill="none" stroke="#0f172a" stroke-width="2">'
            . '<rect x="4" y="4" width="92" height="92" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>'
            . '<rect x="14" y="14" width="22" height="22" fill="#0f172a"/>'
            . '<rect x="64" y="14" width="22" height="22" fill="#0f172a"/>'
            . '<rect x="14" y="64" width="22" height="22" fill="#0f172a"/>'
            . '<rect x="20" y="20" width="10" height="10" fill="#ffffff"/>'
            . '<rect x="70" y="20" width="10" height="10" fill="#ffffff"/>'
            . '<rect x="20" y="70" width="10" height="10" fill="#ffffff"/>'
            . '<rect x="46" y="18" width="8" height="8" fill="#0f172a"/>'
            . '<rect x="44" y="44" width="12" height="12" fill="#0284c7"/>'
            . '<rect x="64" y="52" width="8" height="8" fill="#0f172a"/>'
            . '<rect x="52" y="72" width="10" height="10" fill="#0f172a"/>'
            . '<rect x="72" y="72" width="10" height="10" fill="#0f172a"/>'
            . '</svg>';
    }
}
