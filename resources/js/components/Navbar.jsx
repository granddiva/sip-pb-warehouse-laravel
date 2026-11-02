import React from 'react';
import { Menu, Plus, Bell, ChevronRight, User } from 'lucide-react';

export default function Navbar({ onOpenMobileMenu, currentPage, user, onNavigate }) {
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
      <div className="flex items-center gap-3">
        {currentPage !== 'document-create' && (
          <button
            onClick={() => onNavigate('document-create')}
            className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm shadow-sky-600/30 transition-all hover:shadow"
          >
            <Plus className="w-3.5 h-3.5 stroke-[3]" />
            <span className="hidden sm:inline">Upload PB</span>
          </button>
        )}

        <div className="flex items-center gap-2.5 pl-3 border-l border-slate-200">
          <div className="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-sky-800 flex items-center justify-center font-extrabold text-xs">
            {user?.avatar || 'U'}
          </div>
          <span className="text-xs font-bold text-slate-700 hidden md:inline">
            {user?.name}
          </span>
        </div>
      </div>
    </header>
  );
}
