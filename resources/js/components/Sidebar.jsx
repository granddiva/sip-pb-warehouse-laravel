import React from 'react';
import {
  LayoutDashboard,
  FileText,
  UploadCloud,
  Layers,
  Clock,
  QrCode,
  ShieldCheck,
  LogOut,
  Building2,
  AlertTriangle,
} from 'lucide-react';

export default function Sidebar({
  currentPage,
  navigate,
  user,
  onLogout,
  counts = {},
  isOpen,
  onClose,
}) {
  const isAuditor = user?.is_auditor;

  const rawNavItems = [
    {
      category: 'Navigasi Utama',
      items: [
        { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
        { id: 'documents', label: 'Data Dokumen PB', icon: FileText, badge: counts.documents },
        ...(isAuditor ? [] : [{ id: 'document-create', label: 'Upload Dokumen PB', icon: UploadCloud }]),
      ],
    },
    {
      category: 'Sirkulasi & Fisik Gudang',
      items: [
        {
          id: 'borrowings',
          label: 'Sirkulasi Peminjaman',
          icon: Clock,
          badge: counts.borrowings,
          badgeColor: counts.overdue > 0 ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white',
        },
        { id: 'racks', label: 'Denah 2D Rak & Box', icon: Layers },
        { id: 'qrcodes', label: 'Cetak Label & QR Code', icon: QrCode },
      ],
    },
    {
      category: 'Tata Kelola & Keamanan',
      items: [
        { id: 'activity-logs', label: 'Audit Trail / Log', icon: ShieldCheck },
      ],
    },
  ];

  const navItems = rawNavItems;

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      <aside
        className={`fixed top-0 bottom-0 left-0 w-72 bg-slate-950 text-slate-300 z-50 flex flex-col border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Brand Header */}
        <div
          onClick={() => navigate('dashboard')}
          className="h-20 px-6 flex items-center gap-3.5 border-b border-slate-800/80 cursor-pointer bg-slate-900/50 hover:bg-slate-900 transition-colors"
        >
          <div className="w-11 h-11 rounded-lg bg-white p-1.5 flex items-center justify-center shadow-md flex-shrink-0">
            <img
              src="/images/besmindo-logo.jpg"
              alt="Logo BMS"
              className="w-full h-full object-contain"
              onError={(e) => {
                e.target.style.display = 'none';
                e.target.parentElement.innerHTML = '<span class="text-xs font-black text-sky-700">BMS</span>';
              }}
            />
          </div>
          <div className="overflow-hidden">
            <h1 className="text-base font-extrabold tracking-tight text-white flex items-center gap-1.5">
              <span>SIP-PB WH</span>
              <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-400 border border-sky-500/30">
                v2.0
              </span>
            </h1>
            <p className="text-[10.5px] font-semibold text-slate-400 truncate uppercase tracking-wider">
              PT Besmindo Materi Sewatama
            </p>
          </div>
        </div>

        {/* Nav Items */}
        <div className="flex-1 overflow-y-auto px-4 py-5 space-y-6 scrollbar-thin scrollbar-thumb-slate-800">
          {navItems.map((group, idx) => (
            <div key={idx} className="space-y-1.5">
              <div className="px-3 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                {group.category}
              </div>
              {group.items.map((item) => {
                const Icon = item.icon;
                const isActive = currentPage === item.id || (item.id === 'documents' && currentPage === 'document-detail');

                return (
                  <button
                    key={item.id}
                    onClick={() => {
                      navigate(item.id);
                      if (onClose) onClose();
                    }}
                    className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all group ${
                      isActive
                        ? 'bg-sky-600 text-white shadow-lg shadow-sky-600/30'
                        : 'text-slate-300 hover:text-white hover:bg-slate-900/90'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <Icon
                        className={`w-4 h-4 transition-colors ${
                          isActive ? 'text-white' : 'text-slate-400 group-hover:text-sky-400'
                        }`}
                      />
                      <span>{item.label}</span>
                    </div>

                    {item.badge !== undefined && item.badge > 0 && (
                      <span
                        className={`text-xs px-2 py-0.5 rounded-full font-bold ${
                          item.badgeColor || 'bg-slate-800 text-slate-300'
                        }`}
                      >
                        {item.badge}
                      </span>
                    )}
                  </button>
                );
              })}
            </div>
          ))}
        </div>

        {/* User Card & Logout Footer */}
        <div className="p-4 border-t border-slate-800/80 bg-slate-900/40">
          <div className="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 space-y-2">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-sky-600 to-blue-500 flex items-center justify-center font-bold text-white text-sm shadow-inner flex-shrink-0">
                {user?.avatar || 'AW'}
              </div>
              <div className="flex-1 overflow-hidden">
                <div className="text-sm font-bold text-white truncate">{user?.name || 'User Warehouse'}</div>
                <div className="text-[11px] text-slate-400 truncate">{user?.divisi || user?.role || 'Staff'}</div>
              </div>
              <button
                onClick={onLogout}
                title="Keluar dari sistem"
                className="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-md transition-colors"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>

            {/* Role RBAC Badge */}
            <div className="pt-1 border-t border-slate-800/60 flex items-center justify-between text-[10px]">
              <span className="text-slate-500 font-semibold uppercase tracking-wider">Peran:</span>
              {user?.is_admin_warehouse ? (
                <span className="px-2 py-0.5 rounded-full font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30">
                  Admin Warehouse
                </span>
              ) : user?.is_auditor ? (
                <span className="px-2 py-0.5 rounded-full font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30">
                  Auditor (Read-Only)
                </span>
              ) : (
                <span className="px-2 py-0.5 rounded-full font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 truncate max-w-[150px]">
                  Pemohon Divisi
                </span>
              )}
            </div>
          </div>
        </div>
      </aside>
    </>
  );
}
