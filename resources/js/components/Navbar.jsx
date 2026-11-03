import React from 'react';
import { Menu, Plus, Bell, ChevronRight, User, Camera } from 'lucide-react';

export default function Navbar({ onOpenMobileMenu, currentPage, user, onNavigate, onOpenScanner }) {
  const pageTitles = {
    'dashboard': 'Dashboard & Analitik',
    'documents': 'Daftar Dokumen Permintaan Barang (PB)',
    'document-create': 'Upload Dokumen PB Baru',
    'document-detail': 'Rincian Dokumen PB',
    'racks': 'Visual Denah 2D Rak & Box Gudang',
    'borrowings': 'Sirkulasi Peminjaman Fisik',
    'qrcodes': 'Cetak Label Stiker & QR Code',
    'activity-logs': 'Audit Trail & Riwayat Aktivitas',
  };

  return (
    <header className="sticky top-0 z-30 h-16 bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-4 lg:px-8 flex items-center justify-between">
      {/* Left: Mobile Toggle & Breadcrumbs */}
      <div className="flex items-center gap-3">
        <button
          onClick={onOpenMobileMenu}
          className="lg:hidden p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg"
          aria-label="Buka Menu"
        >
          <Menu className="w-5 h-5" />
        </button>

        <div className="flex items-center gap-2 text-xs md:text-sm font-semibold">
          <span className="text-slate-600 font-bold hidden sm:inline">Warehouse</span>
          <ChevronRight className="w-4 h-4 text-slate-400 hidden sm:inline" />
          <span className="text-slate-900 font-bold truncate">
            {pageTitles[currentPage] || 'Sistem Pengarsipan PB'}
          </span>
        </div>
      </div>

      {/* Right: Action CTA & User Pill */}
      <div className="flex items-center gap-2.5">
        <button
          onClick={onOpenScanner}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-xs transition-all hover:border-sky-300"
          title="Scan Barcode / QR Code (Ctrl + K)"
        >
          <Camera className="w-3.5 h-3.5 text-sky-600" />
          <span className="hidden sm:inline">Scan QR</span>
          <kbd className="hidden lg:inline text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-mono border">Ctrl+K</kbd>
        </button>

        {currentPage !== 'document-create' && !user?.is_auditor && (
          <button
            onClick={() => onNavigate('document-create')}
            className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm shadow-sky-600/30 transition-all hover:shadow"
          >
            <Plus className="w-3.5 h-3.5 stroke-[3]" />
            <span className="hidden sm:inline">Upload PB</span>
          </button>
        )}

        <div className="flex items-center gap-2 pl-3 border-l border-slate-200">
          {/* User Role Badge */}
          {user?.is_admin_warehouse ? (
            <span className="hidden md:inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
              Admin WH
            </span>
          ) : user?.is_auditor ? (
            <span className="hidden md:inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
              Auditor (Read-Only)
            </span>
          ) : (
            <span className="hidden md:inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 max-w-[130px] truncate" title={user?.divisi}>
              {user?.divisi ? user.divisi.split(' ')[0] : 'Pemohon'}
            </span>
          )}

          <div className="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-sky-800 flex items-center justify-center font-extrabold text-xs">
            {user?.avatar || 'U'}
          </div>
          <div className="hidden lg:block text-left">
            <div className="text-xs font-bold text-slate-800 truncate max-w-[120px]">{user?.name}</div>
            <div className="text-[10px] text-slate-400 truncate max-w-[120px]">{user?.divisi || user?.role}</div>
          </div>
        </div>
      </div>
    </header>
  );
}
