<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Document;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function document(Document $document)
    {
        $document->load(['box.rack', 'items']);
        $url = url('/documents/' . $document->id);
        $qrSvg = (string) QrCode::size(120)->generate($url);

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
        $qrSvg = (string) QrCode::size(130)->generate($url);

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
        $type = $request->get('type', 'boxes');

        if ($type === 'documents') {
            $documents = Document::with('box.rack')->latest()->get();
            $items = $documents->map(function ($doc) {
                return [
                    'id'       => $doc->id,
                    'title'    => $doc->nomor_pb,
                    'subtitle' => $doc->bagian,
                    'detail_1' => 'Pemohon: ' . $doc->nama_pemohon,
                    'detail_2' => 'Lokasi: ' . ($doc->lokasi_rak_formatted . ' - ' . $doc->nomor_box_formatted),
                    'detail_3' => 'Tgl: ' . $doc->tanggal_indonesia,
                    'qr_svg'   => (string) QrCode::size(100)->generate(url('/documents/' . $doc->id)),
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
                    'qr_svg'   => (string) QrCode::size(100)->generate(url('/racks?box_id=' . $box->id)),
                ];
            });
        }

        return response()->json([
            'type'  => $type,
            'items' => $items,
        ]);
    }
}
