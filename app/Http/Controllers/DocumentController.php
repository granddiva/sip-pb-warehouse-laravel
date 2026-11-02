<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Box;
use App\Models\Document;
use App\Models\DocumentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private array $bagianList = [
        'Workshop Machining & Threading',
        'NDT & Tubular Inspection',
        'Warehouse & Logistik',
        'HSE & K3L',
        'Maintenance & Utility',
        'Operasional Lapangan',
        'Quality Assurance / QC',
    ];

    private array $statusList = [
        'Terverifikasi & Diarsipkan',
        'Menunggu Arsip Fisik',
        'Dokumen Dipinjam',
        'Selesai Pengadaan',
    ];

    private array $rakList = ['Rak A', 'Rak B', 'Rak C', 'Rak D'];

    public function index(Request $request)
    {
        $query = Document::with('items');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_pb', 'like', "%{$search}%")
                  ->orWhere('nama_pemohon', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($qi) => $qi->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($tanggal = $request->tanggal) {
            $query->whereDate('tanggal_pb', $tanggal);
        }

        if ($bagian = $request->bagian) {
            $query->where('bagian', $bagian);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $documents = $query->latest()->paginate($request->get('per_page', 10))->withQueryString();

        return view('documents.index', [
            'documents'  => $documents,
            'bagianList' => $this->bagianList,
            'statusList' => $this->statusList,
            'filters'    => $request->only(['search', 'tanggal', 'bagian', 'status']),
        ]);
    }

    public function create()
    {
        return view('documents.create', [
            'bagianList' => $this->bagianList,
            'statusList' => $this->statusList,
            'rakList'    => $this->rakList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_pb'      => 'required|string|unique:documents,nomor_pb',
            'tanggal_pb'    => 'required|date',
            'bagian'        => 'required|string',
            'nama_pemohon'  => 'required|string',
            'nip_pemohon'   => 'nullable|string',
            'keterangan'    => 'nullable|string',
            'status'        => 'required|string',
            'lokasi_rak'    => 'nullable|string',
            'nomor_box'     => 'nullable|string',
            'catatan_arsip' => 'nullable|string',
            'file_dokumen'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items'         => 'nullable|array',
            'items.*.nama'  => 'required|string',
        ]);

        $dokumenName = null;
        $dokumenSize = null;
        $dokumenPath = null;

        if ($request->hasFile('file_dokumen')) {
            $file        = $request->file('file_dokumen');
            $dokumenName = $file->getClientOriginalName();
            $dokumenSize = round($file->getSize() / 1024, 1) . ' KB';
            $dokumenPath = $file->store('documents', 'public');
        }

        $doc = Document::create([
            'nomor_pb'      => $validated['nomor_pb'],
            'tanggal_pb'    => $validated['tanggal_pb'],
            'bagian'        => $validated['bagian'],
            'nama_pemohon'  => $validated['nama_pemohon'],
            'nip_pemohon'   => $validated['nip_pemohon'] ?? null,
            'keterangan'    => $validated['keterangan'] ?? null,
            'status'        => $validated['status'],
            'lokasi_rak'    => $validated['lokasi_rak'] ?? null,
            'nomor_box'     => $validated['nomor_box'] ?? null,
            'catatan_arsip' => $validated['catatan_arsip'] ?? null,
            'dokumen'       => $dokumenName,
            'dokumen_size'  => $dokumenSize,
            'dokumen_path'  => $dokumenPath,
            'uploaded_by'   => Auth::user()->name . ' (' . Auth::user()->role . ')',
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                if (!empty($item['nama'])) {
                    $doc->items()->create([
                        'nama'         => $item['nama'],
                        'part_number'  => $item['part_number'] ?? null,
                        'jumlah'       => $item['jumlah'] ?? 1,
                        'satuan'       => $item['satuan'] ?? 'Pcs',
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'spesifikasi'  => $item['spesifikasi'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('documents.index')
            ->with('toast_success', "Dokumen PB {$doc->nomor_pb} berhasil diunggah & diarsipkan");
    }

    public function show(Document $document)
    {
        $document->load('items');
        return view('documents.show', compact('document'));
    }

    public function edit(Document $document)
    {
        $document->load('items');
        return view('documents.edit', [
            'document'   => $document,
            'bagianList' => $this->bagianList,
            'statusList' => $this->statusList,
            'rakList'    => $this->rakList,
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'nomor_pb'      => 'required|string|unique:documents,nomor_pb,' . $document->id,
            'tanggal_pb'    => 'required|date',
            'bagian'        => 'required|string',
            'nama_pemohon'  => 'required|string',
            'nip_pemohon'   => 'nullable|string',
            'keterangan'    => 'nullable|string',
            'status'        => 'required|string',
            'lokasi_rak'    => 'nullable|string',
            'nomor_box'     => 'nullable|string',
            'catatan_arsip' => 'nullable|string',
            'file_dokumen'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items'         => 'nullable|array',
        ]);

        if ($request->hasFile('file_dokumen')) {
            if ($document->dokumen_path) {
                Storage::disk('public')->delete($document->dokumen_path);
            }
            $file                      = $request->file('file_dokumen');
            $validated['dokumen']      = $file->getClientOriginalName();
            $validated['dokumen_size'] = round($file->getSize() / 1024, 1) . ' KB';
            $validated['dokumen_path'] = $file->store('documents', 'public');
        }

        $items = $validated['items'] ?? [];
        unset($validated['items'], $validated['file_dokumen']);

        $document->update($validated);

        // Replace items
        $document->items()->delete();
        foreach ($items as $item) {
            if (!empty($item['nama'])) {
                $document->items()->create([
                    'nama'         => $item['nama'],
                    'part_number'  => $item['part_number'] ?? null,
                    'jumlah'       => $item['jumlah'] ?? 1,
                    'satuan'       => $item['satuan'] ?? 'Pcs',
                    'harga_satuan' => $item['harga_satuan'] ?? 0,
                    'spesifikasi'  => $item['spesifikasi'] ?? null,
                ]);
            }
        }

        return redirect()->route('documents.index')
            ->with('toast_success', "Data dokumen PB {$document->nomor_pb} berhasil diperbarui");
    }

    public function destroy(Document $document)
    {
        $nomor = $document->nomor_pb;
        if ($document->dokumen_path) {
            Storage::disk('public')->delete($document->dokumen_path);
        }
        $document->delete();
        return redirect()->route('documents.index')
            ->with('toast_warning', "Dokumen PB {$nomor} berhasil dihapus dari sistem");
    }
}
