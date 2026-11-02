<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $stats = [
            'total'        => BorrowingLog::count(),
            'dipinjam'     => BorrowingLog::where('status_pinjam', 'Dipinjam')->count(),
            'overdue'      => BorrowingLog::where('status_pinjam', 'Dipinjam')->where('batas_kembali', '<', $today)->count(),
            'dikembalikan' => BorrowingLog::where('status_pinjam', 'Dikembalikan')->count(),
        ];

        $query = BorrowingLog::with(['document.box.rack', 'handledByUser']);

        // Filter tab status
        $statusTab = $request->get('tab', 'all');
        if ($statusTab === 'dipinjam') {
            $query->where('status_pinjam', 'Dipinjam');
        } elseif ($statusTab === 'overdue') {
            $query->where('status_pinjam', 'Dipinjam')->where('batas_kembali', '<', $today);
        } elseif ($statusTab === 'dikembalikan') {
            $query->where('status_pinjam', 'Dikembalikan');
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_peminjam', 'like', "%{$search}%")
                  ->orWhere('departemen_peminjam', 'like', "%{$search}%")
                  ->orWhere('keperluan', 'like', "%{$search}%")
                  ->orWhereHas('document', function ($qd) use ($search) {
                      $qd->where('nomor_pb', 'like', "%{$search}%")
                         ->orWhere('nama_pemohon', 'like', "%{$search}%");
                  });
            });
        }

        $borrowings = $query->latest('tanggal_pinjam')->paginate(10)->withQueryString();

        return view('borrowings.index', compact('borrowings', 'stats', 'statusTab'));
    }

    public function create(Request $request)
    {
        // Dokumen yang valid untuk dipinjam (sudah ada fisik di rak dan belum sedang dipinjam)
        $availableDocs = Document::where('status', 'not like', '%Dipinjam%')
            ->where('status', 'not like', '%Menunggu%')
            ->orderBy('nomor_pb')
            ->get();

        $selectedDocId = $request->get('document_id');
        $selectedDoc = $selectedDocId ? Document::find($selectedDocId) : null;

        return view('borrowings.create', compact('availableDocs', 'selectedDoc'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_id'         => 'required|exists:documents,id',
            'nama_peminjam'       => 'required|string|max:255',
            'departemen_peminjam' => 'required|string|max:255',
            'keperluan'           => 'required|string|max:500',
            'tanggal_pinjam'      => 'required|date',
            'batas_kembali'       => 'required|date|after_or_equal:tanggal_pinjam',
            'catatan'             => 'nullable|string|max:1000',
        ]);

        $document = Document::findOrFail($validated['document_id']);

        // Validasi logika bisnis PRD:
        if (str_contains($document->status, 'Menunggu')) {
            return back()->withInput()->with('toast_warning', 'Gagal meminjam: Berkas fisik dokumen ini belum diserahkan dan diverifikasi di rak gudang.');
        }

        if (str_contains($document->status, 'Dipinjam')) {
            return back()->withInput()->with('toast_warning', 'Gagal meminjam: Dokumen ini sedang dipinjam oleh pihak lain dan belum dikembalikan.');
        }

        $borrowing = BorrowingLog::create([
            'document_id'         => $document->id,
            'handled_by'          => auth()->id(),
            'nama_peminjam'       => $validated['nama_peminjam'],
            'departemen_peminjam' => $validated['departemen_peminjam'],
            'keperluan'           => $validated['keperluan'],
            'tanggal_pinjam'      => $validated['tanggal_pinjam'],
            'batas_kembali'       => $validated['batas_kembali'],
            'status_pinjam'       => 'Dipinjam',
            'catatan'             => $validated['catatan'] ?? null,
        ]);

        // Transisi status dokumen menjadi 'Dokumen Dipinjam'
        $document->update([
            'status' => 'Dokumen Dipinjam',
        ]);

        // Rekam Audit Trail
        ActivityLog::log(
            'borrow',
            "Memproses peminjaman dokumen fisik {$document->nomor_pb} untuk {$borrowing->nama_peminjam} ({$borrowing->departemen_peminjam})"
        );

        return redirect()->route('borrowings.index')
            ->with('toast_success', "Peminjaman dokumen {$document->nomor_pb} berhasil dicatat. Status dokumen kini menjadi Dokumen Dipinjam.");
    }

    public function returnDocument(Request $request, BorrowingLog $borrowing)
    {
        $request->validate([
            'tanggal_kembali' => 'required|date',
            'catatan_kembali' => 'nullable|string|max:1000',
        ]);

        $borrowing->update([
            'status_pinjam'   => 'Dikembalikan',
            'tanggal_kembali' => $request->tanggal_kembali,
            'catatan'         => $borrowing->catatan
                ? $borrowing->catatan . "\n[Pengembalian " . Carbon::parse($request->tanggal_kembali)->format('d/m/Y') . "]: " . ($request->catatan_kembali ?? 'Dokumen fisik lengkap')
                : $request->catatan_kembali,
        ]);

        // Kembalikan status dokumen ke 'Terverifikasi & Diarsipkan'
        if ($borrowing->document) {
            $borrowing->document->update([
                'status' => 'Terverifikasi & Diarsipkan',
            ]);

            ActivityLog::log(
                'return',
                "Pemeriksaan fisik dan pengembalian dokumen {$borrowing->document->nomor_pb} dari peminjam {$borrowing->nama_peminjam}"
            );
        }

        return redirect()->back()
            ->with('toast_success', "Dokumen {$borrowing->document->nomor_pb} telah berhasil dikembalikan ke arsip fisik gudang.");
    }

    public function slip(BorrowingLog $borrowing)
    {
        $borrowing->load(['document.items', 'document.box.rack', 'handledByUser']);
        return view('borrowings.slip', compact('borrowing'));
    }
}
