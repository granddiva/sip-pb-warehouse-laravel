import React, { useState, useEffect } from 'react';
import { ShieldCheck, User, Clock, ChevronLeft, ChevronRight } from 'lucide-react';
import { activityLogApi } from '../api';

export default function AuditTrailPage({ onToast }) {
  const [logs, setLogs] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const fetchLogs = async () => {
    try {
      setLoading(true);
      const res = await activityLogApi.getAll(page);
      setLogs(res.data.data || []);
      setPagination({
        current_page: res.data.current_page,
        last_page: res.data.last_page,
        total: res.data.total,
      });
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat log aktivitas.', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [page]);

  const getActivityBadge = (type) => {
    switch (type) {
      case 'create':
        return <span className="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">CREATE</span>;
      case 'update':
        return <span className="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-bold text-[10px]">UPDATE</span>;
      case 'delete':
        return <span className="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold text-[10px]">DELETE</span>;
      case 'borrow':
        return <span className="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-bold text-[10px]">BORROW</span>;
      case 'return':
        return <span className="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px]">RETURN</span>;
      default:
        return <span className="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] uppercase">{type}</span>;
    }
  };

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <div>
        <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
          <ShieldCheck className="w-6 h-6 text-indigo-600" />
          <span>Audit Trail & Riwayat Aktivitas Sistem</span>
        </h2>
        <p className="text-xs text-slate-500 mt-1">
          Merekam setiap tindakan perubahan data, sirkulasi fisik, dan otentikasi demi transparansi tata kelola warehouse.
        </p>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        {loading ? (
          <div className="py-12 text-center text-slate-400">
            <div className="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
            <span className="text-xs">Memuat jejak audit...</span>
          </div>
        ) : logs.length === 0 ? (
          <div className="text-center py-12 text-slate-400">
            <p className="text-xs font-semibold">Belum ada riwayat aktivitas yang tercatat.</p>
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {logs.map((log) => (
              <div key={log.id} className="p-4 sm:p-5 hover:bg-slate-50/60 transition-colors flex items-start gap-4">
                <div className="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-600 text-xs flex-shrink-0 mt-0.5">
                  <User className="w-4 h-4 text-slate-500" />
                </div>

                <div className="flex-1 space-y-1">
                  <div className="flex items-center justify-between gap-2 flex-wrap">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-slate-900 text-xs">{log.user?.name || 'Sistem Otomatis'}</span>
                      {log.user?.role && (
                        <span className="text-[10px] text-slate-400 font-medium">({log.user.role})</span>
                      )}
                      {getActivityBadge(log.activity_type)}
                    </div>

                    <div className="text-[11px] text-slate-400 flex items-center gap-1 font-mono">
                      <Clock className="w-3 h-3" />
                      <span>{new Date(log.created_at).toLocaleString('id-ID')}</span>
                    </div>
                  </div>

                  <p className="text-xs text-slate-700 leading-relaxed font-medium">
                    {log.description}
                  </p>

                  <div className="text-[10px] text-slate-400 font-mono pt-0.5">
                    IP Address: {log.ip_address || '127.0.0.1'}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Pagination Footer */}
        {pagination.total > 0 && (
          <div className="px-5 py-3.5 bg-slate-50/60 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
            <div>
              Total <strong>{pagination.total}</strong> log aktivitas
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
