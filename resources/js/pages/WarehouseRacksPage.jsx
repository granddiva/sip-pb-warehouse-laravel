import React, { useState, useEffect } from 'react';
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
} from 'lucide-react';
import { rackApi } from '../api';

export default function WarehouseRacksPage({ onNavigate, onToast }) {
  const [racks, setRacks] = useState([]);
  const [selectedRackId, setSelectedRackId] = useState(null);
  const [loading, setLoading] = useState(true);

  // Flyout Drawer state
  const [drawerBox, setDrawerBox] = useState(null);
  const [drawerDocs, setDrawerDocs] = useState([]);
  const [drawerLoading, setDrawerLoading] = useState(false);

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

  const openBoxDrawer = async (box) => {
    setDrawerBox(box);
    setDrawerLoading(true);
    try {
      const res = await rackApi.getBoxDocs(box.id);
      setDrawerDocs(res.data.documents);
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

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="w-10 h-10 border-4 border-sky-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  const currentRack = racks.find((r) => r.id === selectedRackId) || racks[0];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">
            Visual Denah 2D Rak & Box Arsip Fisik Gudang
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            Eksplorasi posisi fisik ordner box di lemari rak, pantau kuota terisi, dan klik box untuk melihat dokumen di dalamnya.
          </p>
        </div>

        <button
          onClick={() => onNavigate('qrcodes')}
          className="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm inline-flex items-center gap-1.5"
        >
          <Printer className="w-4 h-4 text-slate-600" />
          <span>Cetak Label Stiker Box</span>
        </button>
      </div>

      {/* Legend & Status Guide Bar */}
      <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs font-semibold">
        <div className="flex items-center gap-2 text-slate-500 font-bold uppercase text-[10px]">
          <Info className="w-4 h-4 text-sky-600" />
          <span>Panduan Warna Box:</span>
        </div>

        <div className="flex items-center gap-4 flex-wrap">
          <div className="flex items-center gap-1.5">
            <div className="w-3.5 h-3.5 rounded bg-emerald-500" />
            <span className="text-slate-700">Tersedia (&lt; 80%)</span>
          </div>
          <div className="flex items-center gap-1.5">
            <div className="w-3.5 h-3.5 rounded bg-amber-500" />
            <span className="text-slate-700">Hampir Penuh (80-99%)</span>
          </div>
          <div className="flex items-center gap-1.5">
            <div className="w-3.5 h-3.5 rounded bg-rose-500" />
            <span className="text-slate-700">Penuh (100%)</span>
          </div>
          <div className="flex items-center gap-1.5">
            <div className="w-3.5 h-3.5 rounded bg-amber-200 border border-dashed border-amber-600" />
            <span className="text-slate-700">Ada Dokumen Dipinjam</span>
          </div>
        </div>
      </div>

      {/* Rack Selector Tabs */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {racks.map((r) => {
          const isSelected = r.id === currentRack?.id;
          return (
            <button
              key={r.id}
              onClick={() => setSelectedRackId(r.id)}
              className={`p-4 rounded-2xl text-left transition-all border ${
                isSelected
                  ? 'bg-slate-900 text-white border-slate-900 shadow-lg shadow-slate-900/20'
                  : 'bg-white text-slate-700 border-slate-200/80 hover:bg-slate-50'
              }`}
            >
              <div className="flex items-center justify-between">
                <span className="font-mono font-bold text-base">{r.kode_rak}</span>
                <span
                  className={`text-[10px] font-extrabold px-2 py-0.5 rounded-full ${
                    isSelected ? 'bg-sky-500/20 text-sky-300' : 'bg-slate-100 text-slate-600'
                  }`}
                >
                  {r.persentase_kapasitas}%
                </span>
              </div>
              <div className="text-xs font-bold truncate mt-1">{r.nama_rak}</div>
              <div className={`text-[10px] truncate mt-0.5 ${isSelected ? 'text-slate-400' : 'text-slate-500'}`}>
                {r.zona_lokasi}
              </div>
            </button>
          );
        })}
      </div>

      {/* 2D RACK CABINET INTERACTIVE GRID */}
      {currentRack && (
        <div className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 text-sky-800 text-xs font-bold mb-1">
                <span>Lemari Fisik: {currentRack.kode_rak}</span>
                <span>•</span>
                <span>{currentRack.zona_lokasi}</span>
              </div>
              <h3 className="text-lg font-black text-slate-900">{currentRack.nama_rak}</h3>
              <p className="text-xs text-slate-500">{currentRack.keterangan}</p>
            </div>

            <div className="text-right text-xs">
              <span className="text-slate-500">Total Keterisian: </span>
              <strong className="text-slate-900">{currentRack.total_dokumen}</strong>
              <span className="text-slate-500"> / {currentRack.kapasitas_maksimal} Dokumen</span>
              <div className="w-36 h-2 rounded-full bg-slate-100 overflow-hidden mt-1 ml-auto">
                <div
                  className="h-full bg-sky-600 rounded-full"
                  style={{ width: `${currentRack.persentase_kapasitas}%` }}
                />
              </div>
            </div>
          </div>

          {/* Interactive Visual 2D Grid Cabinet */}
          <div className="bg-slate-900 rounded-2xl p-6 sm:p-8 border-4 border-slate-800 shadow-2xl relative overflow-hidden">
            {/* Cabinet Roof Header */}
            <div className="flex items-center justify-between text-slate-400 text-xs font-mono font-bold border-b border-slate-800 pb-3 mb-6">
              <span>// FRONT ELEVATION RACK CABINET: {currentRack.kode_rak}</span>
              <span>GRID: {currentRack.baris || 4} ROWS × {currentRack.kolom || 3} COLUMNS</span>
            </div>

            {/* Boxes Grid */}
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
              {currentRack.boxes.map((box) => {
                const percent = box.persentase_kapasitas;
                const isOverdue = box.has_borrowed_docs;

                // Color themes
                let cardColor = 'bg-slate-800/90 border-emerald-500/50 hover:border-emerald-400 text-emerald-300';
                let badgeColor = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';

                if (box.is_full) {
                  cardColor = 'bg-slate-800/90 border-rose-500/50 hover:border-rose-400 text-rose-300';
                  badgeColor = 'bg-rose-500/20 text-rose-300 border-rose-500/30';
                } else if (box.is_warning) {
                  cardColor = 'bg-slate-800/90 border-amber-500/50 hover:border-amber-400 text-amber-300';
                  badgeColor = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
                }

                if (isOverdue) {
                  cardColor += ' ring-2 ring-amber-400/80 border-dashed';
                }

                return (
                  <div
                    key={box.id}
                    onClick={() => openBoxDrawer(box)}
                    className={`rounded-xl p-4 border transition-all duration-200 cursor-pointer hover:scale-[1.03] hover:shadow-xl relative group flex flex-col justify-between min-h-[140px] ${cardColor}`}
                  >
                    {/* Top Bar of Box */}
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-1.5 font-mono font-bold text-sm text-white">
                        <BoxIcon className="w-4 h-4 text-sky-400" />
                        <span>{box.nomor_box}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        {box.status === 'Penuh (Terkunci)' && (
                          <span className="text-[9px] font-black bg-rose-500/30 text-rose-300 border border-rose-500/50 px-1 py-0.5 rounded flex items-center gap-0.5">
                            <Lock className="w-2.5 h-2.5" />
                            <span>KUNCI</span>
                          </span>
                        )}
                        <span className={`text-[10px] font-extrabold px-1.5 py-0.5 rounded border ${badgeColor}`}>
                          {percent}%
                        </span>
                      </div>
                    </div>

                    {/* Middle Info */}
                    <div className="my-2 space-y-1">
                      <div className="text-[11px] text-slate-300 font-semibold flex items-center justify-between">
                        <span>Dokumen:</span>
                        <strong className="text-white text-xs">{box.total_dokumen} Map</strong>
                      </div>
                      <div className="text-[10px] text-slate-400 flex items-center justify-between">
                        <span>Sisa Kuota:</span>
                        <span>{box.sisa_kapasitas} Slot</span>
                      </div>

                      {/* Small progress meter */}
                      <div className="w-full h-1.5 bg-slate-700/80 rounded-full overflow-hidden mt-1.5">
                        <div
                          className={`h-full rounded-full ${
                            box.is_full ? 'bg-rose-500' : box.is_warning ? 'bg-amber-500' : 'bg-emerald-500'
                          }`}
                          style={{ width: `${percent}%` }}
                        />
                      </div>
                    </div>

                    {/* Bottom Status / Borrowed indicator */}
                    <div className="pt-2 border-t border-slate-700/50 flex items-center justify-between text-[10px]">
                      {isOverdue ? (
                        <span className="text-amber-400 font-bold flex items-center gap-1">
                          <AlertTriangle className="w-3 h-3" />
                          <span>Ada Dipinjam</span>
                        </span>
                      ) : (
                        <span className="text-slate-400 font-mono">B{box.baris_ke} K{box.kolom_ke}</span>
                      )}

                      <span className="text-sky-400 group-hover:translate-x-0.5 transition-transform font-bold flex items-center">
                        Buka &rarr;
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* FLYOUT SIDE DRAWER (When a Box is clicked) */}
      {drawerBox && (
        <div className="fixed inset-0 z-[9999] overflow-hidden">
          {/* Backdrop */}
          <div
            className="absolute inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
            onClick={closeDrawer}
          />

          <div className="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div className="w-screen max-w-md bg-white shadow-2xl flex flex-col border-l border-slate-200 animate-in slide-in-from-right duration-300">
              {/* Drawer Header */}
              <div className="p-6 bg-slate-900 text-white flex items-center justify-between">
                <div>
                  <div className="text-xs font-mono font-bold text-sky-400 uppercase tracking-wider">
                    {currentRack.kode_rak} – {drawerBox.nomor_box}
                  </div>
                  <h3 className="text-base font-extrabold text-white mt-0.5">
                    Daftar Dokumen di Box
                  </h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    Kapasitas: {drawerBox.total_dokumen} / {drawerBox.kapasitas_maksimal} Dokumen ({drawerBox.persentase_kapasitas}%)
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <button
                    onClick={handleToggleBoxLock}
                    className={`px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all ${
                      drawerBox.status === 'Penuh (Terkunci)'
                        ? 'bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-600/30'
                        : 'bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 border border-slate-700'
                    }`}
                    title={drawerBox.status === 'Penuh (Terkunci)' ? 'Klik untuk membuka kunci box' : 'Kunci box ini jika fisik ordner sudah penuh'}
                  >
                    {drawerBox.status === 'Penuh (Terkunci)' ? <Lock className="w-3.5 h-3.5" /> : <Unlock className="w-3.5 h-3.5" />}
                    <span>{drawerBox.status === 'Penuh (Terkunci)' ? 'Terkunci Penuh' : 'Kunci Penuh'}</span>
                  </button>

                  <button
                    onClick={closeDrawer}
                    className="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
              </div>

              {/* Drawer Content */}
              <div className="flex-1 overflow-y-auto p-6 space-y-3">
                {drawerLoading ? (
                  <div className="py-12 text-center text-slate-400">
                    <div className="w-8 h-8 border-3 border-sky-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                    <span className="text-xs">Memuat dokumen box...</span>
                  </div>
                ) : drawerDocs.length === 0 ? (
                  <div className="text-center py-12 text-slate-400 space-y-2">
                    <FolderOpen className="w-10 h-10 mx-auto text-slate-300" />
                    <p className="text-xs font-semibold text-slate-600">Box ini masih kosong</p>
                    <p className="text-[11px] text-slate-400">Belum ada dokumen PB yang dialokasikan ke box ini.</p>
                  </div>
                ) : (
                  drawerDocs.map((doc) => (
                    <div
                      key={doc.id}
                      className="p-4 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-sky-50/50 hover:border-sky-300 transition-all space-y-2"
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

                      <div className="text-xs font-semibold text-slate-800">{doc.nama_pemohon}</div>
                      <div className="text-[11px] text-slate-500">{doc.bagian}</div>

                      {doc.keterangan && (
                        <p className="text-[11px] text-slate-600 italic line-clamp-2">
                          "{doc.keterangan}"
                        </p>
                      )}

                      <div className="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                        <span className="text-[10px] text-slate-400">{doc.tanggal_indonesia}</span>
                        <button
                          onClick={() => {
                            closeDrawer();
                            onNavigate('document-detail', doc.id);
                          }}
                          className="font-bold text-sky-600 hover:text-sky-700 hover:underline flex items-center gap-1"
                        >
                          <span>Buka Detail</span>
                          <ExternalLink className="w-3 h-3" />
                        </button>
                      </div>
                    </div>
                  ))
                )}
              </div>

              {/* Drawer Footer */}
              <div className="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <button
                  onClick={() => onNavigate('qrcodes', { boxId: drawerBox.id })}
                  className="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold flex items-center gap-2"
                >
                  <Printer className="w-3.5 h-3.5" />
                  <span>Cetak Label Stiker Box</span>
                </button>
                <button
                  onClick={closeDrawer}
                  className="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100"
                >
                  Tutup
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
