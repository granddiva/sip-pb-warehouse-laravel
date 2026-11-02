<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Document;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function document(Document $document)
    {
        $document->load(['box.rack', 'items']);
        $url = route('documents.show', $document);
        $qrSvg = QrCode::size(120)->generate($url);

        return view('qrcodes.document', compact('document', 'qrSvg', 'url'));
    }

    public function box(Box $box)
    {
        $box->load(['rack', 'documents']);
        $url = route('racks.index', ['box_id' => $box->id]);
        $qrSvg = QrCode::size(130)->generate($url);

        return view('qrcodes.box', compact('box', 'qrSvg', 'url'));
    }

    public function batch(Request $request)
    {
        $type = $request->get('type', 'boxes'); // 'boxes' or 'documents'

        if ($type === 'documents') {
            $ids = $request->get('ids', []);
            $documents = !empty($ids) 
                ? Document::with('box.rack')->whereIn('id', $ids)->get()
                : Document::with('box.rack')->latest()->take(12)->get();

            $items = $documents->map(function ($doc) {
                return [
                    'title'       => $doc->nomor_pb,
                    'subtitle'    => $doc->bagian,
                    'detail_1'    => 'Pemohon: ' . $doc->nama_pemohon,
                    'detail_2'    => 'Lokasi: ' . ($doc->lokasi_rak_formatted . ' - ' . $doc->nomor_box_formatted),
                    'detail_3'    => 'Tgl: ' . $doc->tanggal_indonesia,
                    'qr_svg'      => QrCode::size(110)->generate(route('documents.show', $doc)),
                ];
            });
        } else {
            $boxes = Box::with('rack')->orderBy('rack_id')->orderBy('nomor_box')->get();
            $items = $boxes->map(function ($box) {
                return [
                    'title'       => ($box->rack?->kode_rak ?? 'RAK') . ' / ' . $box->nomor_box,
                    'subtitle'    => $box->rack?->nama_rak ?? 'Warehouse',
                    'detail_1'    => 'Zona: ' . ($box->rack?->zona_lokasi ?? '-'),
                    'detail_2'    => 'Kapasitas: ' . $box->total_dokumen . '/' . $box->kapasitas_maksimal . ' Ordner',
                    'detail_3'    => 'Status: ' . $box->status,
                    'qr_svg'      => QrCode::size(110)->generate(route('racks.index', ['box_id' => $box->id])),
                ];
            });
        }

        return view('qrcodes.batch', compact('items', 'type'));
    }
}
