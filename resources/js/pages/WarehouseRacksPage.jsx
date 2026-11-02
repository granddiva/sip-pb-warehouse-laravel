import React, { useState, useEffect, useMemo } from 'react';
import {
  Layers,
  Box as BoxIcon,
  FolderOpen,
  Info,
  X,
  ExternalLink,
  Printer,
  ChevronRight,
  AlertTriangle,
  CheckCircle2,
  Lock,
  Unlock,
  Search,
  Compass,
  LayoutGrid,
  Maximize2,
  Filter,
  ShieldCheck,
  Eye,
  Warehouse,
  Grid3x3,
  MapPin,
  Thermometer,
  Flame,
  DoorClosed,
  FileText,
  RotateCcw,
  Sparkles,
  QrCode,
  Tag,
  Check,
} from 'lucide-react';
import { rackApi } from '../api';

export default function WarehouseRacksPage({ onNavigate, onToast }) {
  const [racks, setRacks] = useState([]);
  const [selectedRackId, setSelectedRackId] = useState(null);
  const [loading, setLoading] = useState(true);

  // View Mode: 'elevation' (Tampak Depan Lemari Rak), 'floorplan' (Denah Tata Ruang 2D), or 'split' (Keduanya)
  const [viewMode, setViewMode] = useState('split');

  // Search & Filter state
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all'); // 'all', 'available', 'warning', 'full', 'borrowed', 'locked'

  // Flyout Drawer state
  const [drawerBox, setDrawerBox] = useState(null);
  const [drawerDocs, setDrawerDocs] = useState([]);
  const [drawerLoading, setDrawerLoading] = useState(false);
  const [drawerSearch, setDrawerSearch] = useState('');

  const fetchRacks = async () => {
    try {
      setLoading(true);
      const res = await rackApi.getRacks();
      setRacks(res.data);
      if (res.data.length > 0 && !selectedRackId) {
        setSelectedRackId(res.data[0].id);
      }
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat denah rak gudang.', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRacks();
  }, []);

  // Keyboard shortcut: Escape to close drawer
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && drawerBox) {
        closeDrawer();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [drawerBox]);

  const openBoxDrawer = async (box) => {
    setDrawerBox(box);
    setDrawerSearch('');
    setDrawerLoading(true);
    try {
      const res = await rackApi.getBoxDocs(box.id);
      setDrawerDocs(res.data.documents || []);
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat dokumen di dalam box ini.', 'error');
    } finally {
      setDrawerLoading(false);
    }
  };

  const closeDrawer = () => {
    setDrawerBox(null);
    setDrawerDocs([]);
    setDrawerSearch('');
  };

  const handleToggleBoxLock = async () => {
    if (!drawerBox) return;
    try {
      const res = await rackApi.toggleBoxLock(drawerBox.id);
      onToast(res.data.message || 'Status box berhasil diubah.', 'success');
      setDrawerBox({ ...drawerBox, status: res.data.status });
      fetchRacks();
    } catch (err) {
      console.error(err);
      onToast('Gagal mengubah status box.', 'error');
    }
  };

  const currentRack = racks.find((r) => r.id === selectedRackId) || racks[0];

  // Overall Warehouse Telemetry KPI calculations
  const warehouseStats = useMemo(() => {
    let totalCap = 0;
    let totalDocs = 0;
    let totalBoxes = 0;
    let borrowedBoxesCount = 0;
    let lockedBoxesCount = 0;

    racks.forEach((rack) => {
      totalCap += rack.kapasitas_maksimal || 0;
      totalDocs += rack.total_dokumen || 0;
      if (rack.boxes) {
        totalBoxes += rack.boxes.length;
        rack.boxes.forEach((b) => {
          if (b.has_borrowed_docs) borrowedBoxesCount++;
          if (b.status === 'Penuh (Terkunci)') lockedBoxesCount++;
        });
      }
    });

    const percentage = totalCap > 0 ? Math.round((totalDocs / totalCap) * 100) : 0;

    return {
      totalCap,
      totalDocs,
      totalBoxes,
      borrowedBoxesCount,
      lockedBoxesCount,
      percentage,
    };
  }, [racks]);

  // Check if a box matches the active search query and status filter
  const isBoxMatched = (box) => {
    if (!box) return false;

    // Status filter
    if (statusFilter === 'available' && (box.is_full || box.is_warning)) return false;
    if (statusFilter === 'warning' && !box.is_warning) return false;
    if (statusFilter === 'full' && !box.is_full) return false;
    if (statusFilter === 'borrowed' && !box.has_borrowed_docs) return false;
    if (statusFilter === 'locked' && box.status !== 'Penuh (Terkunci)') return false;

    // Search query
    if (!searchQuery.trim()) return true;

    const query = searchQuery.toLowerCase().trim();
    const boxNumMatch = box.nomor_box?.toLowerCase().includes(query);
    const boxNoteMatch = box.catatan?.toLowerCase().includes(query);

    const docMatch = box.documents?.some(
      (d) =>
        d.nomor_pb?.toLowerCase().includes(query) ||
        d.nama_pemohon?.toLowerCase().includes(query) ||
        d.bagian?.toLowerCase().includes(query)
    );

    return boxNumMatch || boxNoteMatch || docMatch;
  };

  // Filtered documents inside Drawer
  const filteredDrawerDocs = useMemo(() => {
    if (!drawerSearch.trim()) return drawerDocs;
    const q = drawerSearch.toLowerCase().trim();
    return drawerDocs.filter(
      (d) =>
        d.nomor_pb?.toLowerCase().includes(q) ||
        d.nama_pemohon?.toLowerCase().includes(q) ||
        d.bagian?.toLowerCase().includes(q) ||
        d.status?.toLowerCase().includes(q)
    );
  }, [drawerDocs, drawerSearch]);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] space-y-4">
        <div className="w-12 h-12 border-4 border-sky-600 border-t-transparent rounded-full animate-spin" />
        <div className="text-center">
          <p className="text-sm font-black text-slate-800">Menghubungkan ke Denah Fisik Gudang...</p>
          <p className="text-xs text-slate-400 mt-1">Sinkronisasi status rak, ambalan, dan telemetry ordner...</p>
        </div>
      </div>
    );
  }

  // Pre-calculate shelf grid (Rows 1..4, Cols 1..3)
  const rowsCount = currentRack?.baris || 4;
  const colsCount = currentRack?.kolom || 3;

  // Build 2D matrix mapping: matrix[row][col] => box | null
  const shelfMatrix = [];
  for (let r = 1; r <= rowsCount; r++) {
    const rowSlots = [];
    for (let c = 1; c <= colsCount; c++) {
      const box = currentRack?.boxes?.find((b) => b.baris_ke === r && b.kolom_ke === c) || null;
      rowSlots.push({ row: r, col: c, box });
    }
    shelfMatrix.push(rowSlots);
  }

  return (
    <div className="space-y-5">
      {/* 1. TOP HEADER & TELEMETRY KPI PANEL */}
      <div className="bg-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-800 relative overflow-hidden">
        {/* Subtle Blueprint Grid Background Accent */}
        <div
          className="absolute inset-0 opacity-10 pointer-events-none"
          style={{
            backgroundImage:
              'radial-gradient(circle at 1px 1px, #38bdf8 1px, transparent 0)',
            backgroundSize: '24px 24px',
          }}
        />

        <div className="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
          <div className="space-y-1.5">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/10 border border-sky-400/20 text-sky-300 text-[11px] font-bold">
              <Warehouse className="w-3.5 h-3.5" />
              <span>PT BESMINDO MATERI SEWATAMA • WAREHOUSE CENTRAL ARCHIVE</span>
            </div>
            <h2 className="text-xl sm:text-2xl font-black text-white tracking-tight">
              Visual Denah 2D Rak & Box Arsip Fisik Gudang
            </h2>
            <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
              Pemetaan spasial 2 dimensi lemari rak arsip, ambalan struktural, dan ordner box fisik. Dilengkapi status keterisian kuota dan peminjaman berkas secara real-time.
            </p>
          </div>

          <div className="flex items-center gap-2.5 flex-wrap">
            <button
              onClick={() => onNavigate('qrcodes')}
              className="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-lg shadow-sky-600/30 inline-flex items-center gap-2 transition-all"
            >
              <Printer className="w-4 h-4" />
              <span>Cetak Label Stiker Box</span>
            </button>
            <button
              onClick={fetchRacks}
              className="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition-all"
              title="Refresh Denah & Kapasitas"
            >
              <RotateCcw className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Live Warehouse Telemetry Chips */}
        <div className="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-slate-800/80">
          <div className="bg-slate-800/60 rounded-2xl p-3 border border-slate-700/50">
            <div className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
              Total Kapasitas Fisik
            </div>
            <div className="flex items-baseline gap-2 mt-1">
              <span className="text-xl font-black text-white">{warehouseStats.totalDocs}</span>
              <span className="text-xs text-slate-400">/ {warehouseStats.totalCap} Map PB</span>
            </div>
            <div className="w-full bg-slate-700/60 h-1.5 rounded-full mt-2 overflow-hidden">
              <div
                className="bg-sky-500 h-full rounded-full transition-all"
                style={{ width: `${warehouseStats.percentage}%` }}
              />
            </div>
          </div>

          <div className="bg-slate-800/60 rounded-2xl p-3 border border-slate-700/50">
            <div className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
              Utilisasi Gudang
            </div>
            <div className="flex items-baseline gap-2 mt-1">
              <span className="text-xl font-black text-emerald-400">{warehouseStats.percentage}%</span>
              <span className="text-xs text-emerald-300 font-semibold">Terkendali</span>
            </div>
            <div className="text-[10px] text-slate-400 mt-1">
              {warehouseStats.totalBoxes} Ordner Box Terpasang
            </div>
          </div>

          <div className="bg-slate-800/60 rounded-2xl p-3 border border-slate-700/50">
            <div className="text-[10px] font-bold uppercase tracking-wider text-amber-400">
              Peminjaman Aktif
            </div>
            <div className="flex items-baseline gap-2 mt-1">
              <span className="text-xl font-black text-amber-300">{warehouseStats.borrowedBoxesCount}</span>
              <span className="text-xs text-amber-400/90">Box Terdampak</span>
            </div>
            <div className="text-[10px] text-slate-400 mt-1">
              {warehouseStats.borrowedBoxesCount > 0 ? 'Berkas sedang keluar gudang' : 'Semua berkas di tempat'}
            </div>
          </div>

          <div className="bg-slate-800/60 rounded-2xl p-3 border border-slate-700/50">
            <div className="text-[10px] font-bold uppercase tracking-wider text-rose-400">
              Proteksi Box Terkunci
            </div>
            <div className="flex items-baseline gap-2 mt-1">
              <span className="text-xl font-black text-rose-300">{warehouseStats.lockedBoxesCount}</span>
              <span className="text-xs text-rose-400/90">Box Terkunci</span>
            </div>
            <div className="text-[10px] text-slate-400 mt-1">
              Kapasitas ordner fisik penuh
            </div>
          </div>
        </div>
      </div>

      {/* 2. CONTROL TOOLBAR (View Switcher, Search & Filter Bar) */}
      <div className="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        {/* View Mode Toggle */}
        <div className="flex items-center bg-slate-100 p-1 rounded-xl self-start md:self-auto border border-slate-200">
          <button
            onClick={() => setViewMode('split')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all ${
              viewMode === 'split'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <LayoutGrid className="w-3.5 h-3.5 text-sky-600" />
            <span className="hidden sm:inline">Split View (Denah & Elevasi)</span>
            <span className="sm:hidden">Split</span>
          </button>

          <button
            onClick={() => setViewMode('elevation')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all ${
              viewMode === 'elevation'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Grid3x3 className="w-3.5 h-3.5 text-sky-600" />
            <span>Elevasi Rak 2D</span>
          </button>

          <button
            onClick={() => setViewMode('floorplan')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all ${
              viewMode === 'floorplan'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Compass className="w-3.5 h-3.5 text-sky-600" />
            <span>Denah Tata Ruang</span>
          </button>
        </div>

        {/* Global Search Box */}
        <div className="flex items-center gap-2 flex-1 max-w-md">
          <div className="relative w-full">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari Box, Nomor PB, Nama Pemohon, Bagian..."
              className="w-full pl-9 pr-8 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none bg-slate-50 focus:bg-white"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>

        {/* Filter Chips */}
        <div className="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 text-xs">
          <button
            onClick={() => setStatusFilter('all')}
            className={`px-2.5 py-1.5 rounded-lg font-bold transition-all whitespace-nowrap ${
              statusFilter === 'all'
                ? 'bg-slate-900 text-white'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`}
          >
            Semua Box
          </button>
          <button
            onClick={() => setStatusFilter('borrowed')}
            className={`px-2.5 py-1.5 rounded-lg font-bold transition-all whitespace-nowrap flex items-center gap-1 ${
              statusFilter === 'borrowed'
                ? 'bg-amber-500 text-slate-950 font-black shadow-sm'
                : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200'
            }`}
          >
            <AlertTriangle className="w-3 h-3" />
            <span>Ada Dipinjam</span>
          </button>
          <button
            onClick={() => setStatusFilter('locked')}
            className={`px-2.5 py-1.5 rounded-lg font-bold transition-all whitespace-nowrap flex items-center gap-1 ${
              statusFilter === 'locked'
                ? 'bg-rose-600 text-white font-black shadow-sm'
                : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'
            }`}
          >
            <Lock className="w-3 h-3" />
            <span>Terkunci</span>
          </button>
        </div>
      </div>

      {/* 3. MAIN WORKSPACE AREA (Floor Plan and/or Rack Elevation) */}
      <div className="space-y-6">
        {/* ARCHITECTURAL 2D FLOOR PLAN (Top-down view of the facility) */}
        {(viewMode === 'floorplan' || viewMode === 'split') && (
          <div className="bg-slate-950 rounded-3xl p-5 sm:p-6 border border-slate-800 shadow-2xl relative overflow-hidden">
            {/* Top Bar with Blueprint Metadata */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-4 border-b border-slate-800/80 gap-2">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-xl bg-sky-500/10 border border-sky-400/20 text-sky-400 flex items-center justify-center">
                  <Compass className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-mono font-bold text-sky-400 tracking-wider">
                      BLUEPRINT CAD 1:50
                    </span>
                    <span className="text-[10px] text-slate-500 font-mono">
                      // DIMENSI GUDANG: 12.0M × 8.5M
                    </span>
                  </div>
                  <h3 className="text-sm font-black text-white tracking-tight">
                    Denah Tata Letak Ruang Arsip Sentral (Top-Down Bird's Eye View)
                  </h3>
                </div>
              </div>

              <div className="flex items-center gap-3 text-[10px] font-mono text-slate-400 flex-wrap">
                <div className="flex items-center gap-1">
                  <Thermometer className="w-3.5 h-3.5 text-sky-400" />
                  <span>24.2°C • 54% RH (Optimal)</span>
                </div>
                <div className="flex items-center gap-1">
                  <Flame className="w-3.5 h-3.5 text-rose-400" />
                  <span>APAR Siap Pakai</span>
                </div>
                <div className="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-sky-300 font-bold">
                  NORTH ↑
                </div>
              </div>
            </div>

            {/* Architectural Room Blueprint Container */}
            <div
              className="relative rounded-2xl border-2 border-slate-700/80 bg-slate-900/90 p-4 sm:p-6 overflow-hidden min-h-[340px]"
              style={{
                backgroundImage: `
                  linear-gradient(to right, rgba(56, 189, 248, 0.05) 1px, transparent 1px),
                  linear-gradient(to bottom, rgba(56, 189, 248, 0.05) 1px, transparent 1px)
                `,
                backgroundSize: '20px 20px',
              }}
            >
              {/* Outer Dimension Indicators */}
              <div className="absolute top-1 left-3 text-[9px] font-mono text-slate-500">
                P-01 ZONA ARSIP BARAT
              </div>
              <div className="absolute top-1 right-3 text-[9px] font-mono text-slate-500">
                P-02 ZONA ARSIP TIMUR
              </div>

              {/* Safety Exit Indicator */}
              <div className="absolute top-0 right-1/4 -translate-y-1/2 bg-emerald-950 text-emerald-300 border border-emerald-500 px-2 py-0.5 rounded text-[9px] font-mono font-bold flex items-center gap-1">
                <span>🚪 JALUR EVAKUASI</span>
              </div>

              {/* Central Gang / Aisle Marker */}
              <div className="absolute inset-x-8 top-1/2 -translate-y-1/2 h-12 border-y border-dashed border-amber-500/30 flex items-center justify-between px-4 pointer-events-none">
                <span className="text-[10px] font-mono font-bold text-amber-500/50 uppercase tracking-widest">
                  ◀◀ GANG UTAMA KORIDOR 1.8M (JALUR TROLLEY) ▶▶
                </span>
                <span className="text-[10px] font-mono font-bold text-amber-500/50 uppercase tracking-widest">
                  BEBAS HAMBATAN
                </span>
              </div>

              {/* 4 Interactive Rack Bays in 2x2 Spatial Layout */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 relative z-10">
                {racks.map((r) => {
                  const isSelected = r.id === currentRack?.id;
                  const matchingBoxes = r.boxes?.filter(isBoxMatched) || [];
                  const hasMatch = searchQuery.trim() !== '' && matchingBoxes.length > 0;

                  return (
                    <div
                      key={r.id}
                      onClick={() => setSelectedRackId(r.id)}
                      className={`rounded-2xl p-4 transition-all duration-200 cursor-pointer border-2 relative group ${
                        isSelected
                          ? 'bg-slate-800/95 border-sky-400 shadow-xl shadow-sky-500/10 ring-2 ring-sky-500/30'
                          : 'bg-slate-800/60 border-slate-700/80 hover:border-slate-500 hover:bg-slate-800'
                      } ${hasMatch ? 'ring-2 ring-emerald-400 border-emerald-400' : ''}`}
                    >
                      {/* Top Bay Tag */}
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <div
                            className="w-3 h-3 rounded-full"
                            style={{ backgroundColor: r.warna_label || '#0ea5e9' }}
                          />
                          <span className="font-mono font-black text-sm text-white">
                            {r.kode_rak}
                          </span>
                          <span className="text-[11px] font-bold text-slate-300">
                            {r.zona_lokasi}
                          </span>
                        </div>

                        {isSelected ? (
                          <span className="px-2 py-0.5 rounded-md bg-sky-500 text-slate-950 font-black text-[10px] tracking-wide">
                            AKTIF
                          </span>
                        ) : (
                          <span className="text-[10px] font-mono text-slate-400 group-hover:text-white transition-colors">
                            PILIH RAK &rarr;
                          </span>
                        )}
                      </div>

                      <div className="text-xs font-bold text-slate-200 mt-2 truncate">
                        {r.nama_rak}
                      </div>

                      {/* Capacity Progress in Blueprint */}
                      <div className="mt-3 space-y-1.5">
                        <div className="flex items-center justify-between text-[11px] font-mono">
                          <span className="text-slate-400">
                            Keterisian: {r.total_dokumen} / {r.kapasitas_maksimal} Map
                          </span>
                          <span className="font-bold text-sky-400">{r.persentase_kapasitas}%</span>
                        </div>
                        <div className="w-full h-2 bg-slate-900 rounded-full overflow-hidden border border-slate-700/60">
                          <div
                            className="h-full rounded-full transition-all"
                            style={{
                              width: `${r.persentase_kapasitas}%`,
                              backgroundColor: r.warna_label || '#0ea5e9',
                            }}
                          />
                        </div>
                      </div>

                      {/* Search Matches Notification */}
                      {hasMatch && (
                        <div className="mt-2 text-[10px] font-bold text-emerald-400 flex items-center gap-1">
                          <Sparkles className="w-3 h-3" />
                          <span>Ditemukan {matchingBoxes.length} Box cocok</span>
                        </div>
                      )}

                      {/* Interactive Footer */}
                      <div className="mt-3 pt-2.5 border-t border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>{r.boxes?.length || 0} Box Terpasang</span>
                        <span className="text-sky-300 font-bold group-hover:underline">
                          Tampilkan Elevasi 2D &rarr;
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Bottom Facilities Bar in Room Blueprint */}
              <div className="mt-6 pt-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-400 font-mono">
                {/* Physical Staging / Inspection Desk */}
                <div className="flex items-center gap-2 bg-slate-850 px-3 py-1.5 rounded-xl border border-slate-700/60 text-slate-300">
                  <div className="w-2.5 h-2.5 rounded-sm bg-amber-500" />
                  <span>[📋 MEJA VERIFIKASI FISIK & STEMPEL BAP GUDANG]</span>
                </div>

                {/* Entry Door */}
                <div className="flex items-center gap-2 bg-slate-850 px-3 py-1.5 rounded-xl border border-slate-700/60 text-slate-300">
                  <DoorClosed className="w-4 h-4 text-sky-400" />
                  <span>[🚪 PINTU AKSES UTAMA - RFID SECURITY]</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* STEEL SHELVING FRONT ELEVATION 2D (Tampak Depan Konstruksi Rak Baja) */}
        {(viewMode === 'elevation' || viewMode === 'split') && currentRack && (
          <div className="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-sm space-y-6">
            {/* Header: Rack Identity & Navigation Tabs */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <span
                    className="w-3 h-3 rounded-full"
                    style={{ backgroundColor: currentRack.warna_label || '#0ea5e9' }}
                  />
                  <span className="text-xs font-mono font-bold text-sky-700 uppercase tracking-wider">
                    {currentRack.kode_rak} • {currentRack.zona_lokasi}
                  </span>
                  <span className="text-slate-300">|</span>
                  <span className="text-xs text-slate-500">
                    Konstruksi Rak: {rowsCount} Tingkat Ambalan × {colsCount} Kolom
                  </span>
                </div>
                <h3 className="text-xl font-black text-slate-900 tracking-tight">
                  {currentRack.nama_rak}
                </h3>
                <p className="text-xs text-slate-500 mt-0.5">{currentRack.keterangan}</p>
              </div>

              {/* Horizontal Rack Quick Switcher Tabs */}
              <div className="flex items-center gap-1.5 flex-wrap">
                {racks.map((r) => {
                  const isCurrent = r.id === currentRack.id;
                  return (
                    <button
                      key={r.id}
                      onClick={() => setSelectedRackId(r.id)}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all border ${
                        isCurrent
                          ? 'bg-slate-900 text-white border-slate-900 shadow-md'
                          : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                      }`}
                    >
                      <span>{r.kode_rak}</span>
                      <span className="ml-1.5 opacity-70 font-mono text-[10px]">
                        ({r.persentase_kapasitas}%)
                      </span>
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Industrial Steel Shelving Cabinet (Tampak Depan Lemari Rak Fisik) */}
            <div className="bg-slate-950 rounded-3xl p-4 sm:p-6 border-4 border-slate-800 shadow-2xl relative overflow-hidden">
              {/* Cabinet Roof Header Beam */}
              <div className="bg-slate-900 text-slate-300 rounded-t-xl px-4 py-2.5 border-b-2 border-slate-700 flex items-center justify-between text-xs font-mono font-bold">
                <div className="flex items-center gap-2">
                  <div className="w-2.5 h-2.5 rounded-full bg-emerald-400" />
                  <span>// TAMPAK DEPAN ELEVASI RAK FISIK BAJA: {currentRack.kode_rak}</span>
                </div>
                <div className="hidden sm:flex items-center gap-3 text-[11px] text-slate-400">
                  <span>DIMENSI: 2000H × 1200W × 450D (MM)</span>
                  <span>BEBAN MAKS: 150 KG / AMBALAN</span>
                </div>
              </div>

              {/* Column Guides on Top of Cabinet */}
              <div className="grid grid-cols-12 gap-3 px-2 sm:px-6 py-2 bg-slate-900/60 border-b border-slate-800 text-[10px] font-mono font-bold text-slate-400">
                <div className="col-span-2 sm:col-span-1 text-center">TINGKAT</div>
                <div className="col-span-10 sm:col-span-11 grid grid-cols-3 gap-3 sm:gap-4 text-center">
                  <div>KOLOM 1 (KIRI)</div>
                  <div>KOLOM 2 (TENGAH)</div>
                  <div>KOLOM 3 (KANAN)</div>
                </div>
              </div>

              {/* Steel Shelving Levels (Iterate Tier Rows from Top to Bottom) */}
              <div className="p-2 sm:p-5 space-y-4">
                {shelfMatrix.map((rowSlots, rowIdx) => {
                  const rowNumber = rowIdx + 1; // Baris 1 to 4
                  return (
                    <div key={rowNumber} className="relative">
                      {/* Row Grid: Left Tier Indicator + 3 Compartment Slots */}
                      <div className="grid grid-cols-12 gap-2 sm:gap-4 items-center">
                        {/* Upright Slotted Pillar & Tier Label */}
                        <div className="col-span-2 sm:col-span-1 flex flex-col items-center justify-center p-2 rounded-xl bg-slate-900 border border-slate-800 text-center font-mono">
                          <span className="text-[10px] text-slate-400">TIER</span>
                          <span className="text-sm font-black text-white">B{rowNumber}</span>
                          <span className="text-[9px] text-slate-500">Tingkat {rowNumber}</span>
                        </div>

                        {/* 3 Compartment Slots on this Shelf Beam */}
                        <div className="col-span-10 sm:col-span-11 grid grid-cols-3 gap-2.5 sm:gap-4">
                          {rowSlots.map(({ col, box }) => {
                            // If Slot has a Box:
                            if (box) {
                              const isMatched = isBoxMatched(box);
                              const isBorrowed = box.has_borrowed_docs;
                              const isLocked = box.status === 'Penuh (Terkunci)';
                              const percent = box.persentase_kapasitas;

                              // Visual ordner spine themes
                              let borderAccent = 'border-emerald-500/50 hover:border-emerald-400';
                              let spineBadge = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
                              let progressColor = 'bg-emerald-500';

                              if (box.is_full || isLocked) {
                                borderAccent = 'border-rose-500/60 hover:border-rose-400';
                                spineBadge = 'bg-rose-500/20 text-rose-300 border-rose-500/40';
                                progressColor = 'bg-rose-500';
                              } else if (box.is_warning) {
                                borderAccent = 'border-amber-500/60 hover:border-amber-400';
                                spineBadge = 'bg-amber-500/20 text-amber-300 border-amber-500/40';
                                progressColor = 'bg-amber-500';
                              }

                              if (isBorrowed) {
                                borderAccent = 'border-amber-400 ring-2 ring-amber-400/80 border-dashed';
                              }

                              return (
                                <div
                                  key={box.id}
                                  onClick={() => openBoxDrawer(box)}
                                  className={`rounded-2xl p-3 sm:p-4 bg-slate-900 border-2 transition-all duration-200 cursor-pointer hover:-translate-y-1 hover:shadow-2xl relative group flex flex-col justify-between min-h-[145px] sm:min-h-[160px] ${borderAccent} ${
                                    !isMatched && searchQuery.trim() !== '' ? 'opacity-30' : 'opacity-100'
                                  }`}
                                >
                                  {/* Top Spine Header */}
                                  <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-1.5 font-mono font-bold text-xs sm:text-sm text-white">
                                      <BoxIcon className="w-4 h-4 text-sky-400 flex-shrink-0" />
                                      <span>{box.nomor_box}</span>
                                    </div>

                                    <div className="flex items-center gap-1">
                                      {isLocked && (
                                        <span className="text-[9px] font-black bg-rose-500/20 text-rose-300 border border-rose-500/40 px-1 py-0.5 rounded flex items-center gap-0.5">
                                          <Lock className="w-2.5 h-2.5" />
                                          <span>KUNCI</span>
                                        </span>
                                      )}
                                      <span
                                        className={`text-[9px] sm:text-[10px] font-black px-1.5 py-0.5 rounded border ${spineBadge}`}
                                      >
                                        {percent}%
                                      </span>
                                    </div>
                                  </div>

                                  {/* Ergonomic Finger Pull Ring (Tactile Ordner Feel) */}
                                  <div className="my-1.5 flex items-center justify-center">
                                    <div className="w-5 h-5 rounded-full border-2 border-slate-700 bg-slate-950 flex items-center justify-center shadow-inner group-hover:border-sky-400 transition-colors">
                                      <div className="w-1.5 h-1.5 rounded-full bg-slate-700 group-hover:bg-sky-400 transition-colors" />
                                    </div>
                                  </div>

                                  {/* Middle Metadata & Progress */}
                                  <div className="space-y-1">
                                    <div className="text-[11px] text-slate-300 font-semibold flex items-center justify-between">
                                      <span>Keterisian:</span>
                                      <strong className="text-white text-xs">
                                        {box.total_dokumen} / {box.kapasitas_maksimal} Map
                                      </strong>
                                    </div>

                                    {/* Progress meter */}
                                    <div className="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                      <div
                                        className={`h-full rounded-full transition-all ${progressColor}`}
                                        style={{ width: `${percent}%` }}
                                      />
                                    </div>
                                  </div>

                                  {/* Bottom Box Footer */}
                                  <div className="pt-2 border-t border-slate-800 flex items-center justify-between text-[10px]">
                                    {isBorrowed ? (
                                      <span className="text-amber-400 font-bold flex items-center gap-1 animate-pulse">
                                        <AlertTriangle className="w-3 h-3" />
                                        <span className="hidden sm:inline">Ada Dipinjam</span>
                                        <span className="sm:hidden">Pinjam</span>
                                      </span>
                                    ) : (
                                      <span className="text-slate-400 font-mono">
                                        B{rowNumber}-K{col}
                                      </span>
                                    )}

                                    <span className="text-sky-400 font-bold group-hover:translate-x-0.5 transition-transform flex items-center gap-0.5">
                                      <span>Inspeksi</span>
                                      <ChevronRight className="w-3 h-3" />
                                    </span>
                                  </div>
                                </div>
                              );
                            }

                            // If Slot is Empty on this Shelf Ambalan:
                            return (
                              <div
                                key={`empty-${rowNumber}-${col}`}
                                className="rounded-2xl p-3 sm:p-4 bg-slate-950/60 border-2 border-dashed border-slate-800 flex flex-col items-center justify-center text-center min-h-[145px] sm:min-h-[160px] space-y-1.5 text-slate-600 hover:border-slate-700 transition-colors"
                              >
                                <div className="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600 font-mono text-xs">
                                  +
                                </div>
                                <span className="text-xs font-mono font-bold text-slate-500">
                                  Slot Kosong B{rowNumber}K{col}
                                </span>
                                <span className="text-[10px] text-slate-600">
                                  Siap untuk ordner baru (20 Map)
                                </span>
                              </div>
                            );
                          })}
                        </div>
                      </div>

                      {/* Heavy Duty Steel Ambalan Beam (Floor of this shelf tier) */}
                      <div className="mt-2.5 h-3 bg-gradient-to-r from-slate-700 via-slate-600 to-slate-700 rounded-sm shadow-md border-t border-slate-500/40 relative flex items-center justify-center">
                        <div className="w-12 h-0.5 bg-slate-400/30 rounded-full" />
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Steel Base Cabinet Bottom Footer */}
              <div className="mt-2 pt-3 border-t-2 border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-400 px-3">
                <span>TIANG BAJA UTAMA (UPRIGHT STRUCTURAL STEEL BASE)</span>
                <span>STATUS STRUKTUR: KOKOH TERJANGKAR BAUT LANTAI</span>
              </div>
            </div>

            {/* Industrial Color Legend Bar */}
            <div className="bg-slate-50 rounded-2xl p-3.5 border border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs font-semibold">
              <div className="flex items-center gap-2 text-slate-700 font-bold uppercase text-[10px]">
                <Info className="w-4 h-4 text-sky-600" />
                <span>Legenda Status Fisik Ordner:</span>
              </div>

              <div className="flex items-center gap-4 flex-wrap text-slate-700 text-xs">
                <div className="flex items-center gap-1.5">
                  <div className="w-3.5 h-3.5 rounded bg-emerald-500" />
                  <span>Tersedia (&lt; 80%)</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <div className="w-3.5 h-3.5 rounded bg-amber-500" />
                  <span>Hampir Penuh (80-99%)</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <div className="w-3.5 h-3.5 rounded bg-rose-500" />
                  <span>Penuh / Terkunci (100%)</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <div className="w-3.5 h-3.5 rounded bg-slate-900 border-2 border-dashed border-amber-400" />
                  <span>Ada Dokumen Dipinjam</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <div className="w-3.5 h-3.5 rounded border border-dashed border-slate-400 bg-slate-200" />
                  <span className="text-slate-500">Slot Ambalan Kosong</span>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* 4. MODAL 2-KOLOM 'ORDNER DOSSIER' DI TENGAH LAYAR (Inspeksi Fisik Box & Manifest PB) */}
      {drawerBox && (
        <div className="fixed inset-0 z-[9999] bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 overflow-hidden flex flex-col max-h-[92vh]">
            {/* Top Modal Header */}
            <div className="px-5 sm:px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 flex-shrink-0">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center flex-shrink-0">
                  <BoxIcon className="w-5 h-5" />
                </div>
                <div>
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-[10px] font-mono font-bold tracking-wider px-2 py-0.5 rounded-md bg-sky-500/10 text-sky-300 border border-sky-400/20">
                      PASPOR FISIK ORDNER
                    </span>
                    <span className="text-xs font-mono font-black text-white">
                      {currentRack?.kode_rak} • {drawerBox.nomor_box}
                    </span>
                    <span className="text-[11px] font-mono text-slate-400">
                      [Tingkat Ambalan B{drawerBox.baris_ke} - Kolom {drawerBox.kolom_ke}]
                    </span>
                  </div>
                  <h3 className="text-sm sm:text-base font-black text-white tracking-tight mt-0.5">
                    Meja Inspeksi Berkas & Verifikasi Fisik Ordner
                  </h3>
                </div>
              </div>

              <div className="flex items-center gap-2 flex-shrink-0">
                <button
                  onClick={handleToggleBoxLock}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all ${
                    drawerBox.status === 'Penuh (Terkunci)'
                      ? 'bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-600/30'
                      : 'bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 border border-slate-700'
                  }`}
                  title={
                    drawerBox.status === 'Penuh (Terkunci)'
                      ? 'Klik untuk membuka kunci box'
                      : 'Kunci box ini jika fisik ordner sudah penuh'
                  }
                >
                  {drawerBox.status === 'Penuh (Terkunci)' ? (
                    <Lock className="w-3.5 h-3.5" />
                  ) : (
                    <Unlock className="w-3.5 h-3.5" />
                  )}
                  <span className="hidden sm:inline">
                    {drawerBox.status === 'Penuh (Terkunci)' ? 'Terkunci Penuh' : 'Kunci Penuh'}
                  </span>
                </button>

                <button
                  onClick={closeDrawer}
                  className="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
                  title="Tutup Meja Periksa (Esc)"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Hierarchical Location Ribbon (Fixed text overflow) */}
            <div className="bg-slate-100 px-5 sm:px-6 py-2 border-b border-slate-200 text-xs font-medium text-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 flex-shrink-0">
              <div className="flex items-center gap-1.5 flex-wrap text-[11px]">
                <span className="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Lokasi Gudang:</span>
                <span className="font-bold text-slate-900">{currentRack?.nama_rak}</span>
                <ChevronRight className="w-3 h-3 text-slate-400 flex-shrink-0" />
                <span className="text-slate-700">Ambalan B{drawerBox.baris_ke} (Tingkat {drawerBox.baris_ke})</span>
                <ChevronRight className="w-3 h-3 text-slate-400 flex-shrink-0" />
                <span className="text-slate-700 font-semibold">Kolom {drawerBox.kolom_ke}</span>
              </div>

              <div className="flex items-center gap-2 text-[11px] font-mono self-start sm:self-auto">
                <span className="px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700 font-bold">
                  Terisi: {drawerBox.total_dokumen} / {drawerBox.kapasitas_maksimal} Map
                </span>
                <span className="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold">
                  Sisa: {drawerBox.sisa_kapasitas} Slot
                </span>
              </div>
            </div>

            {/* Modal Body: 2-Column Responsive Master-Detail Layout */}
            <div className="grid grid-cols-1 lg:grid-cols-12 flex-1 overflow-y-auto">
              {/* LEFT COLUMN (5 Cols): Paspor Fisik, Pratinjau Stiker Thermal & Kuota */}
              <div className="lg:col-span-5 p-5 bg-slate-50/70 border-b lg:border-b-0 lg:border-r border-slate-200 space-y-4 flex flex-col justify-between">
                <div className="space-y-4">
                  {/* Pratinjau Stiker Thermal Fisik (Realistic Warehouse Label) */}
                  <div>
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-500">
                        Pratinjau Label Stiker Box:
                      </span>
                      <span className="text-[10px] text-slate-400 font-mono">Thermal 100×50mm</span>
                    </div>

                    <div className="bg-white rounded-2xl p-4 border-2 border-slate-300 shadow-sm relative overflow-hidden space-y-2.5">
                      <div className="flex items-center justify-between border-b border-dashed border-slate-200 pb-2">
                        <div className="text-[10px] font-bold text-slate-800 tracking-tight leading-tight">
                          PT BESMINDO MATERI SEWATAMA
                          <div className="text-[9px] text-slate-500 font-normal">Sistem Pengarsipan Dokumen PB</div>
                        </div>
                        <div className="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center p-1">
                          <QrCode className="w-full h-full text-white" />
                        </div>
                      </div>

                      <div className="flex items-baseline justify-between pt-0.5">
                        <div>
                          <div className="text-xl font-mono font-black text-slate-900 tracking-tight">
                            {drawerBox.nomor_box}
                          </div>
                          <div className="text-[10px] font-mono font-bold text-sky-700">
                            {currentRack?.kode_rak} • B{drawerBox.baris_ke}-K{drawerBox.kolom_ke}
                          </div>
                        </div>

                        <div className="text-right">
                          <span
                            className={`text-[9px] font-extrabold px-2 py-0.5 rounded-full border ${
                              drawerBox.status === 'Penuh (Terkunci)'
                                ? 'bg-rose-100 text-rose-800 border-rose-300'
                                : drawerBox.persentase_kapasitas >= 80
                                ? 'bg-amber-100 text-amber-800 border-amber-300'
                                : 'bg-emerald-100 text-emerald-800 border-emerald-300'
                            }`}
                          >
                            {drawerBox.status}
                          </span>
                        </div>
                      </div>

                      <div className="text-[10px] text-slate-600 bg-slate-50 rounded-lg p-2 border border-slate-100 flex items-center justify-between">
                        <span className="truncate">{currentRack?.zona_lokasi}</span>
                        <span className="font-mono font-bold text-slate-800 whitespace-nowrap ml-2">
                          Maks: {drawerBox.kapasitas_maksimal} Map
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Meter Utilisasi Kuota Fisik */}
                  <div className="bg-white rounded-2xl p-4 border border-slate-200 space-y-2">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-bold text-slate-700">Keterisian Fisik Ordner</span>
                      <strong className="text-slate-900 font-mono text-sm">
                        {drawerBox.persentase_kapasitas}%
                      </strong>
                    </div>

                    <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                      <div
                        className={`h-full rounded-full transition-all ${
                          drawerBox.is_full || drawerBox.status === 'Penuh (Terkunci)'
                            ? 'bg-rose-500'
                            : drawerBox.is_warning
                            ? 'bg-amber-500'
                            : 'bg-emerald-500'
                        }`}
                        style={{ width: `${drawerBox.persentase_kapasitas}%` }}
                      />
                    </div>

                    <div className="text-[11px] text-slate-500 flex items-center justify-between pt-1">
                      <span>{drawerBox.total_dokumen} Map terarsip</span>
                      <span className="font-bold text-emerald-700">
                        {drawerBox.sisa_kapasitas} slot tersedia
                      </span>
                    </div>
                  </div>

                  {/* Audit Alert jika ada berkas dipinjam */}
                  {drawerBox.has_borrowed_docs && (
                    <div className="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-2.5">
                      <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                      <div className="text-xs">
                        <div className="font-bold">Peringatan Audit Fisik</div>
                        <p className="text-[11px] text-amber-800 mt-0.5 leading-relaxed">
                          Terdapat berkas PB dari box ini yang sedang dipinjam keluar gudang. Harap pantau tanggal batas pengembalian.
                        </p>
                      </div>
                    </div>
                  )}

                  {/* Catatan Ordner */}
                  {drawerBox.catatan && (
                    <div className="text-xs text-slate-600 bg-white p-3 rounded-2xl border border-slate-200">
                      <span className="font-bold text-slate-700 block mb-1">Catatan Ordner:</span>
                      <p className="text-[11px] text-slate-500 italic leading-relaxed">
                        "{drawerBox.catatan}"
                      </p>
                    </div>
                  )}
                </div>

                {/* Left Column Bottom Action */}
                <button
                  onClick={() => onNavigate('qrcodes')}
                  className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all"
                >
                  <Printer className="w-4 h-4 text-sky-400" />
                  <span>Cetak Label Thermal Box Ini</span>
                </button>
              </div>

              {/* RIGHT COLUMN (7 Cols): Manifest Dokumen PB di Dalam Box */}
              <div className="lg:col-span-7 p-5 sm:p-6 flex flex-col justify-between space-y-4">
                <div className="space-y-3.5">
                  {/* Search Bar & Document Count */}
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div>
                      <h4 className="text-xs font-black uppercase tracking-wider text-slate-700">
                        Manifest Berkas PB di Dalam Ordner
                      </h4>
                      <p className="text-[11px] text-slate-500">
                        Ditemukan {filteredDrawerDocs.length} dari {drawerDocs.length} berkas terdaftar
                      </p>
                    </div>

                    <div className="relative w-full sm:w-60">
                      <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                      <input
                        type="text"
                        value={drawerSearch}
                        onChange={(e) => setDrawerSearch(e.target.value)}
                        placeholder="Cari No. PB, pemohon..."
                        className="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none bg-slate-50 focus:bg-white"
                      />
                    </div>
                  </div>

                  {/* Document List Container */}
                  <div className="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                    {drawerLoading ? (
                      <div className="py-16 text-center text-slate-400">
                        <div className="w-8 h-8 border-3 border-sky-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                        <span className="text-xs font-semibold">Memuat manifest dokumen box...</span>
                      </div>
                    ) : filteredDrawerDocs.length === 0 ? (
                      /* Enhanced Empty State (Clean, welcoming & informative) */
                      <div className="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-8 text-center space-y-3">
                        <div className="w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-sm text-slate-400 flex items-center justify-center mx-auto">
                          <FolderOpen className="w-6 h-6 text-sky-500" />
                        </div>
                        <div>
                          <h5 className="text-sm font-black text-slate-800">
                            {drawerSearch ? 'Tidak Ada Berkas yang Cocok' : 'Ordner Fisik Siap Pakai (Kosong)'}
                          </h5>
                          <p className="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">
                            {drawerSearch
                              ? `Tidak ditemukan berkas dengan kata kunci "${drawerSearch}". Coba kata kunci lain.`
                              : `Box ini dalam kondisi bersih dan belum memiliki berkas PB teralokasi. Kapasitas penuh sebanyak ${drawerBox.kapasitas_maksimal} map siap untuk digunakan.`}
                          </p>
                        </div>

                        {!drawerSearch && (
                          <div className="pt-2 flex items-center justify-center gap-2">
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                              <span>Ambalan B{drawerBox.baris_ke} • Kolom {drawerBox.kolom_ke} Siap Digunakan</span>
                            </span>
                          </div>
                        )}
                      </div>
                    ) : (
                      filteredDrawerDocs.map((doc) => (
                        <div
                          key={doc.id}
                          className="p-3.5 rounded-2xl border border-slate-200/90 bg-white hover:bg-sky-50/40 hover:border-sky-300 transition-all space-y-2 shadow-xs group"
                        >
                          <div className="flex items-center justify-between">
                            <span className="font-mono font-bold text-sky-700 text-xs">
                              {doc.nomor_pb}
                            </span>
                            <span
                              className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                doc.status.includes('Terverifikasi')
                                  ? 'bg-emerald-100 text-emerald-700'
                                  : doc.status.includes('Dipinjam')
                                  ? 'bg-rose-100 text-rose-700'
                                  : 'bg-amber-100 text-amber-700'
                              }`}
                            >
                              {doc.status}
                            </span>
                          </div>

                          <div className="text-xs font-semibold text-slate-800 flex items-center justify-between">
                            <span>{doc.nama_pemohon}</span>
                            <span className="text-[11px] text-slate-500 font-normal">{doc.bagian}</span>
                          </div>

                          {doc.keterangan && (
                            <p className="text-[11px] text-slate-600 italic line-clamp-2">
                              "{doc.keterangan}"
                            </p>
                          )}

                          <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span className="text-[10px] text-slate-400 font-mono">
                              {doc.tanggal_indonesia}
                            </span>
                            <button
                              onClick={() => {
                                closeDrawer();
                                onNavigate('document-detail', doc.id);
                              }}
                              className="font-bold text-sky-600 hover:text-sky-700 group-hover:underline flex items-center gap-1"
                            >
                              <span>Buka Dokumen PB</span>
                              <ExternalLink className="w-3 h-3" />
                            </button>
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </div>

                {/* Right Column Bottom Status & Action */}
                <div className="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                  <div className="text-[11px] text-slate-400">
                    Tekan <kbd className="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-600 font-mono font-bold">Esc</kbd> untuk menutup
                  </div>

                  <button
                    onClick={closeDrawer}
                    className="px-5 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors"
                  >
                    Tutup Meja Periksa
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
