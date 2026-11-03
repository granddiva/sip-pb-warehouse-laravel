import React, { useState, useEffect } from 'react';
import {
  FileText,
  CheckCircle2,
  Clock,
  AlertTriangle,
  FolderOpen,
  ArrowRight,
  TrendingUp,
  Layers,
  ShieldCheck,
  Calendar,
  ExternalLink,
} from 'lucide-react';
import { dashboardApi } from '../api';

export default function DashboardPage({ onNavigate, onToast, onOpenReturnModal, refreshKey }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchDashboard = async () => {
    try {
      setLoading(true);
      const res = await dashboardApi.getStats();
      setData(res.data);
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat data dashboard.', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboard();
  }, [refreshKey]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="text-center space-y-3">
          <div className="w-10 h-10 border-4 border-sky-600 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-xs font-bold text-slate-500">Memuat metrik analitik gudang...</p>
        </div>
      </div>
    );
  }

  const stats = data?.stats || {};
  const racks = data?.racks || [];
  const overdueBorrowings = data?.overdueBorrowings || [];
  const recentDocs = data?.recentDocs || [];
  const recentActivities = data?.recentActivities || [];

  return (
    <div className="space-y-6">
      {/* Hero Welcome Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-slate-800 to-sky-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div className="absolute right-0 top-0 bottom-0 w-96 bg-radial from-sky-500/20 to-transparent pointer-events-none" />
        <div className="relative z-10 max-w-2xl">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 text-xs font-bold border border-sky-500/30 mb-3">
            <span>Enterprise Warehouse Management v2.0</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
            SIP-PB WH Monitoring Dashboard
          </h2>
          <p className="text-slate-300 text-xs sm:text-sm mt-2 leading-relaxed">
            Sistem pengarsipan terpadu untuk menjembatani dokumen digital dengan tata letak berkas fisik di rak & box gudang PT Besmindo Materi Sewatama.
          </p>
        </div>
      </div>

      {/* OVERDUE ALERT BANNER (If any overdue) */}
      {overdueBorrowings.length > 0 && (
        <div className="bg-rose-50 border-2 border-rose-300 rounded-2xl p-5 shadow-md animate-in fade-in slide-in-from-top-2">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div className="flex items-start gap-3.5">
              <div className="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                <AlertTriangle className="w-5 h-5 stroke-[2.5]" />
              </div>
              <div>
                <h4 className="text-sm font-extrabold text-rose-900 flex items-center gap-2">
                  <span>PERINGATAN KETERLAMBATAN PENGEMBALIAN ARSIP FISIK ({overdueBorrowings.length} Dokumen)</span>
                </h4>
                <p className="text-xs text-rose-700 mt-0.5">
                  Terdapat dokumen yang melewati batas tanggal jatuh tempo pengembalian. Segera lakukan penagihan berkas fisik kepada peminjam.
                </p>
              </div>
            </div>
            <button
              onClick={() => onNavigate('borrowings')}
              className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm whitespace-nowrap transition-colors flex items-center gap-1.5"
            >
              <span>Periksa Daftar Pinjam</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      )}

      {/* 4 Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center flex-shrink-0">
            <FileText className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-slate-900">{stats.total || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Total Dokumen PB
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
            <CheckCircle2 className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-emerald-600">{stats.verified || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Terverifikasi & Di Rak
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
            <Clock className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-amber-600">{stats.pending || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Menunggu Berkas Fisik
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
            <FolderOpen className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-black text-rose-600">{stats.borrowed || 0}</div>
            <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mt-0.5">
              Sedang Dipinjam
            </div>
          </div>
        </div>
      </div>

      {/* Main Grid: Racks Overview & Recent Docs */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left: Dynamic Rack Capacity (2 Cols) */}
        <div className="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
          <div className="flex items-center justify-between border-b border-slate-100 pb-4">
            <div className="flex items-center gap-2.5">
              <Layers className="w-5 h-5 text-sky-600" />
              <h3 className="text-base font-extrabold text-slate-900">Kapasitas Rak Fisik Gudang (Real-Time)</h3>
            </div>
            <button
              onClick={() => onNavigate('racks')}
              className="text-xs font-bold text-sky-600 hover:text-sky-700 hover:underline flex items-center gap-1"
            >
              <span>Lihat Denah 2D</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            {racks.map((rack) => (
              <div
                key={rack.id}
                onClick={() => onNavigate('racks')}
                className="p-4 rounded-xl border border-slate-200 hover:border-sky-300 hover:shadow-md transition-all cursor-pointer bg-slate-50/50"
              >
                <div className="flex items-center justify-between">
                  <div className="font-extrabold text-slate-900 text-sm">{rack.kode_rak}</div>
                  <span
                    className={`text-[11px] font-extrabold px-2 py-0.5 rounded-full ${
                      rack.persentase_kapasitas >= 90
                        ? 'bg-rose-100 text-rose-700'
                        : rack.persentase_kapasitas >= 50
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-emerald-100 text-emerald-700'
                    }`}
                  >
                    {rack.status_kapasitas}
                  </span>
                </div>

                <div className="text-xs font-semibold text-slate-600 truncate mt-1">
                  {rack.nama_rak}
                </div>
                <div className="text-[11px] text-slate-400 mt-0.5">
                  {rack.zona_lokasi}
                </div>

                {/* Progress bar */}
                <div className="mt-3">
                  <div className="flex justify-between text-[11px] font-bold text-slate-500 mb-1">
                    <span>Terisi: {rack.total_dokumen} Dokumen</span>
                    <span>{rack.persentase_kapasitas}%</span>
                  </div>
                  <div className="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
                    <div
                      className="h-full rounded-full transition-all duration-500"
                      style={{
                        width: `${Math.max(5, rack.persentase_kapasitas)}%`,
                        backgroundColor: rack.warna_label || '#0284c7',
                      }}
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Right: Activity Audit Trail Feed (1 Col) */}
        <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
          <div className="flex items-center justify-between border-b border-slate-100 pb-4">
            <div className="flex items-center gap-2">
              <ShieldCheck className="w-5 h-5 text-indigo-600" />
              <h3 className="text-base font-extrabold text-slate-900">Aktivitas Terkini</h3>
            </div>
            <button
              onClick={() => onNavigate('activity-logs')}
              className="text-xs font-bold text-indigo-600 hover:underline"
            >
              Semua
            </button>
          </div>

          <div className="space-y-3 pt-1">
            {recentActivities.map((log) => (
              <div key={log.id} className="text-xs border-b border-slate-100 pb-2.5 last:border-0 last:pb-0">
                <div className="font-semibold text-slate-800 leading-snug">{log.description}</div>
                <div className="flex items-center justify-between text-[10px] text-slate-400 mt-1 font-medium">
                  <span>{log.user?.name || 'Sistem'}</span>
                  <span>{new Date(log.created_at).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Recent Documents Table */}
      <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h3 className="text-base font-extrabold text-slate-900">Dokumen PB Terakhir Diunggah</h3>
            <p className="text-xs text-slate-500 mt-0.5">Daftar arsip dokumen permintaan barang paling baru</p>
          </div>
          <button
            onClick={() => onNavigate('documents')}
            className="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition-colors"
          >
            <span>Buka Seluruh Dokumen</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-200 text-[11px] font-extrabold uppercase text-slate-500 tracking-wider">
                <th className="py-3 px-3">Nomor PB</th>
                <th className="py-3 px-3">Departemen</th>
                <th className="py-3 px-3">Pemohon</th>
                <th className="py-3 px-3">Tanggal</th>
                <th className="py-3 px-3">Lokasi Fisik</th>
                <th className="py-3 px-3">Status</th>
                <th className="py-3 px-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {recentDocs.map((doc) => (
                <tr key={doc.id} className="hover:bg-slate-50/70 transition-colors">
                  <td className="py-3 px-3 font-mono font-bold text-sky-700">
                    {doc.nomor_pb}
                  </td>
                  <td className="py-3 px-3 font-medium text-slate-700">{doc.bagian}</td>
                  <td className="py-3 px-3 font-semibold text-slate-900">{doc.nama_pemohon}</td>
                  <td className="py-3 px-3 text-slate-600">{doc.tanggal_indonesia}</td>
                  <td className="py-3 px-3">
                    <span className="px-2 py-0.5 rounded bg-sky-50 text-sky-800 border border-sky-200 font-bold text-[11px]">
                      {doc.lokasi_rak} – {doc.nomor_box}
                    </span>
                  </td>
                  <td className="py-3 px-3">
                    <span
                      className={`px-2.5 py-1 rounded-full text-[11px] font-bold ${
                        doc.status.includes('Terverifikasi')
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                          : doc.status.includes('Dipinjam')
                          ? 'bg-rose-50 text-rose-700 border border-rose-200'
                          : doc.status.includes('Selesai')
                          ? 'bg-blue-50 text-blue-700 border border-blue-200'
                          : 'bg-amber-50 text-amber-700 border border-amber-200'
                      }`}
                    >
                      {doc.status}
                    </span>
                  </td>
                  <td className="py-3 px-3 text-right">
                    <button
                      onClick={() => onNavigate('document-detail', doc.id)}
                      className="px-3 py-1 rounded-lg bg-slate-100 hover:bg-sky-50 hover:text-sky-700 text-slate-700 font-bold text-xs transition-colors"
                    >
                      Buka
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
