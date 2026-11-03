import React, { useState, useEffect } from 'react';
import {
  Clock,
  CheckCircle,
  AlertTriangle,
  FolderOpen,
  Search,
  Plus,
  Printer,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Download,
  X,
} from 'lucide-react';
import { borrowingApi } from '../api';

export default function BorrowingsPage({
  user,
  onNavigate,
  onToast,
  onOpenReturnModal,
  onOpenBorrowModal,
  refreshKey,
}) {
  const canManageWarehouse = user?.can_manage_warehouse;
  const [data, setData] = useState({ stats: {}, borrowings: { data: [] } });
  const [tab, setTab] = useState('all');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const fetchBorrowings = async () => {
    try {
      setLoading(true);
      const res = await borrowingApi.getAll({
        tab,
        search,
        page,
      });
      setData(res.data);
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat sirkulasi peminjaman.', 'error');
    } finally {
      setLoading(false);
    }
  };

  // Debounced live search & reactive auto-refresh
  useEffect(() => {
    const timer = setTimeout(() => {
      fetchBorrowings();
    }, 200);
    return () => clearTimeout(timer);
  }, [tab, search, page, refreshKey]);

  const stats = data.stats || {};
  const borrowings = data.borrowings?.data || [];
  const pagination = data.borrowings || {};

  return (
    <div className="space-y-6">
      {/* Header & CTA */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">
            Sirkulasi Peminjaman Berkas Fisik
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            Pantau peminjam dokumen, tanggal jatuh tempo, verifikasi pengembalian, dan cetak slip tanda terima arsip.
          </p>
        </div>

        <div className="flex items-center gap-2.5">
          <a
            href="/api/borrowings/export"
            download
            className="px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5 transition-all"
            title="Unduh Rekap Sirkulasi Peminjaman Dokumen format Excel/CSV"
          >
            <Download className="w-4 h-4 text-emerald-600" />
            <span>Ekspor Excel</span>
          </a>
          {canManageWarehouse ? (
            <button
              onClick={() => onOpenBorrowModal(null)}
              className="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/30 flex items-center gap-2 transition-all"
            >
              <Plus className="w-4 h-4 stroke-[3]" />
              <span>+ Catat Peminjaman Baru</span>
            </button>
          ) : (
            <button
              disabled
              className="px-4 py-2.5 rounded-xl bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed flex items-center gap-2"
              title="Sirkulasi peminjaman hanya dapat diproses oleh Admin/Petugas Warehouse"
            >
              <Plus className="w-4 h-4 stroke-[3]" />
              <span>+ Catat Peminjaman Baru</span>
            </button>
          )}
        </div>
      </div>

      {/* 4 KPI Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center flex-shrink-0">
            <Clock className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-slate-900">{stats.total || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Total Riwayat Sirkulasi
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
            <FolderOpen className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-amber-600">{stats.dipinjam || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Sedang Dipinjam
            </div>
          </div>
        </div>

        <div className={`bg-white rounded-2xl p-5 border shadow-sm flex items-center gap-4 ${
          stats.overdue > 0 ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200/80'
        }`}>
          <div className="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
            <AlertTriangle className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-rose-600">{stats.overdue || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Terlambat (Overdue)
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
            <CheckCircle className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-emerald-600">{stats.dikembalikan || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Telah Dikembalikan
            </div>
          </div>
        </div>
      </div>

      {/* Tabs & Search Filter Card */}
      <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-4">
        {/* Status Tabs */}
        <div className="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-semibold border-b border-slate-100">
          {[
            { id: 'all', label: `Semua Peminjaman (${stats.total || 0})` },
            { id: 'dipinjam', label: `Sedang Dipinjam (${stats.dipinjam || 0})` },
            { id: 'overdue', label: `⚠️ Terlambat / Overdue (${stats.overdue || 0})`, alert: stats.overdue > 0 },
            { id: 'dikembalikan', label: `Sudah Dikembalikan (${stats.dikembalikan || 0})` },
          ].map((item) => (
            <button
              key={item.id}
              onClick={() => { setTab(item.id); setPage(1); }}
              className={`px-4 py-2.5 rounded-lg border-b-2 font-bold transition-all whitespace-nowrap ${
                tab === item.id
                  ? 'border-sky-600 text-sky-700 bg-sky-50/60'
                  : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50'
              } ${item.alert ? 'text-rose-600' : ''}`}
            >
              {item.label}
            </button>
          ))}
        </div>

        {/* Search Input with Auto-Refresh Feedback */}
        <div className="relative">
          <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            type="text"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            placeholder="Cari nama peminjam, departemen, nomor PB, atau keperluan..."
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
      </div>

      {/* Table Card */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-extrabold uppercase text-slate-500 tracking-wider">
                <th className="py-3.5 px-4">Dokumen PB</th>
                <th className="py-3.5 px-4">Peminjam & Departemen</th>
                <th className="py-3.5 px-4">Keperluan</th>
                <th className="py-3.5 px-4">Jadwal Pinjam & Due Date</th>
                <th className="py-3.5 px-4">Status</th>
                <th className="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {loading ? (
                <tr>
                  <td colSpan={6} className="py-12 text-center text-slate-400">
                    <div className="w-8 h-8 border-3 border-sky-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                    <span>Memuat riwayat peminjaman...</span>
                  </td>
                </tr>
              ) : borrowings.length === 0 ? (
                <tr>
                  <td colSpan={6} className="py-12 text-center text-slate-400">
                    <Clock className="w-10 h-10 mx-auto mb-2 text-slate-300" />
                    <p className="font-semibold text-slate-600">Tidak ada data peminjaman</p>
                    <p className="text-[11px] text-slate-400 mt-0.5">Semua berkas tersimpan rapi di rak fisik gudang.</p>
                  </td>
                </tr>
              ) : (
                borrowings.map((b) => (
                  <tr key={b.id} className="hover:bg-slate-50/70 transition-colors">
                    <td className="py-3.5 px-4">
                      <button
                        onClick={() => onNavigate('document-detail', b.document_id)}
                        className="font-mono font-bold text-sky-700 hover:underline block text-left"
                      >
                        {b.nomor_pb}
                      </button>
                      <div className="text-[11px] text-slate-400 mt-0.5">{b.lokasi}</div>
                    </td>

                    <td className="py-3.5 px-4">
                      <div className="font-bold text-slate-900">{b.nama_peminjam}</div>
                      <div className="text-[11px] text-slate-500">{b.departemen_peminjam}</div>
                    </td>

                    <td className="py-3.5 px-4 max-w-xs">
                      <p className="text-slate-700 truncate" title={b.keperluan}>
                        {b.keperluan}
                      </p>
                    </td>

                    <td className="py-3.5 px-4 whitespace-nowrap">
                      <div className="text-slate-600">Pinjam: <strong>{b.tanggal_pinjam}</strong></div>
                      <div className="mt-0.5">
                        Jatuh Tempo: <strong className={b.is_overdue ? 'text-rose-600 underline' : 'text-slate-900'}>{b.batas_kembali}</strong>
                      </div>
                      {b.tanggal_kembali && (
                        <div className="text-[11px] text-emerald-600 font-semibold mt-0.5">
                          Kembali: {b.tanggal_kembali}
                        </div>
                      )}
                    </td>

                    <td className="py-3.5 px-4 whitespace-nowrap">
                      {b.status_pinjam === 'Dikembalikan' ? (
                        <div>
                          <span className="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ✓ Dikembalikan
                          </span>
                          {b.kondisi_fisik && (
                            <div className="text-[10px] text-slate-500 font-medium mt-1">
                              BAP: <span className={b.kondisi_fisik === 'Baik & Utuh' ? 'text-emerald-700 font-bold' : 'text-amber-700 font-bold'}>
                                {b.kondisi_fisik}
                              </span>
                            </div>
                          )}
                        </div>
                      ) : b.is_overdue ? (
                        <span className="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                          ⚠️ Terlambat ({Math.abs(b.sisa_hari)} Hari)
                        </span>
                      ) : (
                        <span className="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                          ⏳ Sedang Dipinjam
                        </span>
                      )}
                    </td>

                    <td className="py-3.5 px-4 text-right whitespace-nowrap">
                      <div className="inline-flex items-center gap-2">
                        <a
                          href={b.slip_url}
                          target="_blank"
                          rel="noreferrer"
                          title="Cetak Slip Tanda Terima"
                          className="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        >
                          <Printer className="w-3.5 h-3.5" />
                        </a>

                        {b.status_pinjam === 'Dipinjam' && canManageWarehouse && (
                          <button
                            onClick={() => onOpenReturnModal(b.id, b.nomor_pb, b.nama_peminjam)}
                            className="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm"
                          >
                            Kembalikan
                          </button>
                        )}
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
              Total <strong>{pagination.total}</strong> riwayat transaksi sirkulasi
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
