import React, { useState, useEffect } from 'react';
import {
  Search,
  Filter,
  Plus,
  Eye,
  Edit,
  Trash2,
  QrCode,
  FileText,
  Paperclip,
  CheckCircle2,
  Clock,
  FolderOpen,
  ChevronLeft,
  ChevronRight,
  Download,
  ShieldCheck,
  AlertTriangle,
  RotateCcw,
  X,
} from 'lucide-react';
import { documentApi } from '../api';

export default function DocumentsPage({ onNavigate, onToast, onOpenVerificationModal, refreshKey }) {
  const [documents, setDocuments] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [quickFilter, setQuickFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [bagianFilter, setBagianFilter] = useState('');
  const [page, setPage] = useState(1);

  const bagianList = [
    'Workshop Machining & Threading',
    'NDT & Tubular Inspection',
    'Warehouse & Logistik',
    'HSE & K3L',
    'Maintenance & Utility',
    'Operasional Lapangan',
    'Quality Assurance / QC',
  ];

  const statusList = [
    'Terverifikasi & Diarsipkan',
    'Menunggu Arsip Fisik',
    'Perlu Revisi Fisik',
    'Dokumen Dipinjam',
    'Selesai Pengadaan',
  ];

  const fetchDocuments = async () => {
    try {
      setLoading(true);
      const res = await documentApi.getAll({
        search,
        quick: quickFilter,
        status: statusFilter,
        bagian: bagianFilter,
        page,
        per_page: 10,
      });

      setDocuments(res.data.data);
      setPagination({
        current_page: res.data.current_page,
        last_page: res.data.last_page,
        total: res.data.total,
      });
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat data dokumen.', 'error');
    } finally {
      setLoading(false);
    }
  };

  // Debounced live search & reactive auto-refresh
  useEffect(() => {
    const timer = setTimeout(() => {
      fetchDocuments();
    }, 200);
    return () => clearTimeout(timer);
  }, [search, quickFilter, statusFilter, bagianFilter, page, refreshKey]);

  const handleDelete = async (id, nomorPb, status) => {
    if (status.includes('Dipinjam')) {
      onToast(`Dokumen ${nomorPb} tidak dapat dihapus karena berkas fisik sedang dipinjam keluar.`, 'warning');
      return;
    }

    if (!window.confirm(`Yakin ingin menghapus dokumen PB ${nomorPb} beserta lampiran fisiknya?`)) {
      return;
    }

    try {
      const res = await documentApi.delete(id);
      onToast(res.data.message || 'Dokumen berhasil dihapus.', 'success');
      fetchDocuments();
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal menghapus dokumen.';
      onToast(msg, 'error');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">Data Dokumen Permintaan Barang (PB)</h2>
          <p className="text-xs text-slate-500 mt-1">
            Kelola arsip dokumen digital, penempatan ordner fisik di rak gudang, dan pencarian instan.
          </p>
        </div>

        <div className="flex items-center gap-2.5">
          <a
            href="/api/documents/export"
            download
            className="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5 transition-all"
            title="Unduh Rekap Dokumen PB format Excel/CSV"
          >
            <Download className="w-4 h-4 text-emerald-600" />
            <span>Ekspor Excel</span>
          </a>
          <button
            onClick={() => onNavigate('qrcodes')}
            className="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5 transition-all"
          >
            <QrCode className="w-4 h-4 text-slate-600" />
            <span>Label & QR Code</span>
          </button>
          <button
            onClick={() => onNavigate('document-create')}
            className="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/30 flex items-center gap-1.5 transition-all"
          >
            <Plus className="w-4 h-4 stroke-[3]" />
            <span>+ Upload PB Baru</span>
          </button>
        </div>
      </div>

      {/* Filter Card */}
      <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-4">
        {/* Quick Filter Pills */}
        <div className="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-semibold">
          <span className="text-slate-400 font-bold uppercase text-[10px] tracking-wider pr-1">Filter Cepat:</span>
          {[
            { id: '', label: 'Semua Dokumen' },
            { id: 'today', label: 'Hari Ini' },
            { id: 'month', label: 'Bulan Ini' },
            { id: 'borrowed', label: 'Sedang Dipinjam' },
            { id: 'pending_physical', label: 'Menunggu Fisik' },
            { id: 'need_revision', label: 'Perlu Revisi' },
          ].map((item) => (
            <button
              key={item.id}
              onClick={() => { setQuickFilter(item.id); setPage(1); }}
              className={`px-3 py-1.5 rounded-full transition-all whitespace-nowrap ${
                quickFilter === item.id
                  ? 'bg-slate-900 text-white shadow'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              }`}
            >
              {item.label}
            </button>
          ))}
        </div>

        {/* Search & Dropdown Filters Grid */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 pt-1">
          {/* Search Box with Real-Time Auto-Refresh Feedback */}
          <div className="md:col-span-6 relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              type="text"
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              placeholder="Cari nomor PB, nama pemohon, spesifikasi item barang..."
              className="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
            {loading ? (
              <div className="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none">
                <div className="w-3.5 h-3.5 border-2 border-sky-600 border-t-transparent rounded-full animate-spin" />
              </div>
            ) : search ? (
              <button
                type="button"
                onClick={() => { setSearch(''); setPage(1); }}
                className="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 rounded-md"
                title="Hapus pencarian"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            ) : null}
          </div>

          {/* Bagian Filter */}
          <div className="md:col-span-3">
            <select
              value={bagianFilter}
              onChange={(e) => { setBagianFilter(e.target.value); setPage(1); }}
              className="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white"
            >
              <option value="">Semua Departemen</option>
              {bagianList.map((b) => (
                <option key={b} value={b}>{b}</option>
              ))}
            </select>
          </div>

          {/* Status Filter */}
          <div className="md:col-span-3">
            <select
              value={statusFilter}
              onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
              className="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white"
            >
              <option value="">Semua Status Arsip</option>
              {statusList.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Table Card */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-extrabold uppercase text-slate-500 tracking-wider">
                <th className="py-3.5 px-4">Nomor PB & Departemen</th>
                <th className="py-3.5 px-4">Tanggal PB</th>
                <th className="py-3.5 px-4">Pemohon</th>
                <th className="py-3.5 px-4">Lokasi Fisik (Rak - Box)</th>
                <th className="py-3.5 px-4">Lampiran</th>
                <th className="py-3.5 px-4">Status</th>
                <th className="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {loading ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-slate-400">
                    <div className="w-8 h-8 border-3 border-sky-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                    <span>Memuat berkas dokumen...</span>
                  </td>
                </tr>
              ) : documents.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-slate-400">
                    <FileText className="w-10 h-10 mx-auto mb-2 text-slate-300" />
                    <p className="font-semibold text-slate-600">Tidak ada dokumen ditemukan</p>
                    <p className="text-[11px] text-slate-400 mt-0.5">Ubah kata kunci pencarian atau bersihkan filter di atas.</p>
                  </td>
                </tr>
              ) : (
                documents.map((doc) => (
                  <tr key={doc.id} className="hover:bg-slate-50/70 transition-colors">
                    <td className="py-3.5 px-4">
                      <button
                        onClick={() => onNavigate('document-detail', doc.id)}
                        className="font-mono font-bold text-sky-700 hover:text-sky-900 hover:underline text-left block"
                      >
                        {doc.nomor_pb}
                      </button>
                      <div className="text-[11px] text-slate-500 font-medium mt-0.5">{doc.bagian}</div>
                    </td>
                    <td className="py-3.5 px-4 text-slate-700 font-medium whitespace-nowrap">
                      {doc.tanggal_indonesia}
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-semibold text-slate-900">{doc.nama_pemohon}</div>
                      <div className="text-[11px] text-slate-400 font-mono">{doc.nip_pemohon || '–'}</div>
                    </td>
                    <td className="py-3.5 px-4 whitespace-nowrap">
                      <span className="px-2.5 py-1 rounded-md bg-sky-50 text-sky-800 border border-sky-200 font-bold text-[11px]">
                        {doc.lokasi_rak} – {doc.nomor_box}
                      </span>
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-2 text-slate-600 font-medium text-[11px]">
                        <span className="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded">
                          <FileText className="w-3 h-3 text-slate-400" />
                          <span>{doc.total_items} Item</span>
                        </span>
                        <span className="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded">
                          <Paperclip className="w-3 h-3 text-slate-400" />
                          <span>{doc.total_attachments} File</span>
                        </span>
                      </div>
                    </td>
                    <td className="py-3.5 px-4 whitespace-nowrap">
                      <span
                        className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold ${
                          doc.status.includes('Terverifikasi')
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : doc.status.includes('Revisi')
                            ? 'bg-gradient-to-r from-rose-50 to-amber-50 text-rose-800 border border-rose-300 font-extrabold shadow-sm'
                            : doc.status.includes('Dipinjam')
                            ? 'bg-rose-50 text-rose-700 border border-rose-200'
                            : doc.status.includes('Selesai')
                            ? 'bg-blue-50 text-blue-700 border border-blue-200'
                            : 'bg-amber-50 text-amber-700 border border-amber-200'
                        }`}
                      >
                        {doc.status.includes('Revisi') && <AlertTriangle className="w-3 h-3 text-rose-600" />}
                        <span>{doc.status}</span>
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-right whitespace-nowrap">
                      <div className="inline-flex items-center gap-1">
                        {doc.status.includes('Menunggu') && onOpenVerificationModal && (
                          <button
                            onClick={() => onOpenVerificationModal(doc)}
                            title="Verifikasi keabsahan fisik berkas hardcopy & simpan ke rak"
                            className="px-2.5 py-1 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold text-[11px] flex items-center gap-1 shadow-sm mr-1 transition-all"
                          >
                            <ShieldCheck className="w-3.5 h-3.5" />
                            <span>Verifikasi Fisik</span>
                          </button>
                        )}
                        {doc.status.includes('Revisi') && (
                          <button
                            onClick={() => onNavigate('document-edit', doc.id)}
                            title="Perbaiki & lengkapi berkas yang memerlukan revisi"
                            className="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] flex items-center gap-1 shadow-sm mr-1 transition-all"
                          >
                            <RotateCcw className="w-3.5 h-3.5" />
                            <span>Revisi Berkas</span>
                          </button>
                        )}
                        <button
                          onClick={() => onNavigate('document-detail', doc.id)}
                          title="Lihat Rincian Dokumen"
                          className="p-1.5 rounded-lg text-slate-500 hover:text-sky-600 hover:bg-sky-50 transition-colors"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => onNavigate('document-edit', doc.id)}
                          title="Edit Dokumen"
                          className="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(doc.id, doc.nomor_pb, doc.status)}
                          title="Hapus Dokumen"
                          className="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Footer */}
        {pagination.total > 0 && (
          <div className="px-5 py-3.5 bg-slate-50/60 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
            <div>
              Menampilkan <strong>{documents.length}</strong> dari <strong>{pagination.total}</strong> dokumen PB
            </div>

            <div className="flex items-center gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage(page - 1)}
                className="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:pointer-events-none font-bold text-xs flex items-center gap-1"
              >
                <ChevronLeft className="w-3.5 h-3.5" />
                <span>Sebelumnya</span>
              </button>
              <span className="font-bold px-2">
                Halaman {pagination.current_page} / {pagination.last_page}
              </span>
              <button
                disabled={page >= pagination.last_page}
                onClick={() => setPage(page + 1)}
                className="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:pointer-events-none font-bold text-xs flex items-center gap-1"
              >
                <span>Berikutnya</span>
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
