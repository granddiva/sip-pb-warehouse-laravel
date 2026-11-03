import React, { useState } from 'react';
import {
  ArrowRightLeft,
  X,
  CheckCircle2,
  AlertTriangle,
  Building2,
  Lock,
  Box as BoxIcon,
  ShieldAlert,
  Sparkles,
} from 'lucide-react';
import { documentApi } from '../api';

export default function RelocateDocumentsModal({
  isOpen,
  onClose,
  documents = [], // single document or array of documents
  racks = [],
  onToast,
  onSuccess,
}) {
  if (!isOpen) return null;

  const docList = Array.isArray(documents) ? documents : [documents];
  if (docList.length === 0) return null;

  const currentDoc = docList[0];
  const currentBoxId = currentDoc?.box_id;
  const currentRackKode = currentDoc?.lokasi_rak || currentDoc?.lokasi_rak_formatted || 'Rak A';

  // Selection states
  const [selectedRackId, setSelectedRackId] = useState(() => {
    const defaultRack = racks.find((r) => r.kode_rak === currentRackKode) || racks[0];
    return defaultRack?.id || (racks[0]?.id ?? null);
  });

  const selectedRack = racks.find((r) => r.id === selectedRackId) || racks[0];
  const availableBoxes = selectedRack?.boxes || [];

  const [selectedBoxId, setSelectedBoxId] = useState(() => {
    // Pick first box that is not current box and not full
    const diffBox = availableBoxes.find((b) => b.id !== currentBoxId && b.status !== 'Penuh (Terkunci)' && b.sisa_kapasitas >= docList.length);
    return diffBox ? diffBox.id : (availableBoxes[0]?.id ?? null);
  });

  const selectedBox = availableBoxes.find((b) => b.id === selectedBoxId);

  const [alasan, setAlasan] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const quickPresets = [
    'Reorganisasi ordner pengadaan',
    'Penyatuan arsip tahun 2026',
    'Box asal kapasitas penuh',
    'Koreksi penempatan ambalan fisik',
  ];

  const handleApplyPreset = (preset) => {
    setAlasan((prev) => (prev ? `${prev}. ${preset}` : preset));
  };

  const isBoxValid =
    selectedBox &&
    selectedBox.id !== currentBoxId &&
    selectedBox.status !== 'Penuh (Terkunci)' &&
    selectedBox.sisa_kapasitas >= docList.length;

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!isBoxValid) {
      onToast('Pilih box tujuan yang valid, berbeda dari box asal, dan memiliki kuota sisa yang cukup.', 'error');
      return;
    }

    if (!alasan.trim()) {
      onToast('Harap isi alasan Berita Acara Relokasi (BAR).', 'warning');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        document_ids: docList.map((d) => d.id),
        target_rack_id: selectedRack.id,
        target_box_id: selectedBox.id,
        alasan: alasan.trim(),
      };

      const res = await documentApi.relocate(payload);
      onToast(res.data.message || 'Dokumen berhasil direlokasi.', 'success');
      onSuccess(res.data.target_box);
      onClose();
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.message || 'Gagal memindahkan dokumen.';
      onToast(msg, 'error');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5">
      <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 overflow-hidden flex flex-col">
        {/* Top Header */}
        <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center">
              <ArrowRightLeft className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-mono font-bold tracking-wider px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-300">
                  MUTASI FISIK ARSIP
                </span>
                <span className="text-xs font-mono text-slate-400">
                  {docList.length} Berkas Dipindahkan
                </span>
              </div>
              <h3 className="text-sm sm:text-base font-black text-white tracking-tight mt-0.5">
                Relokasi Berkas PB Antar-Box / Antar-Rak Gudang
              </h3>
            </div>
          </div>

          <button
            onClick={onClose}
            disabled={submitting}
            className="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form Body - 2 Columns */}
        <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-12 flex-1">
          {/* LEFT COLUMN (5 Cols): Ringkasan Berkas Asal & Rules */}
          <div className="lg:col-span-5 p-5 bg-slate-50 border-b lg:border-b-0 lg:border-r border-slate-200 space-y-4 flex flex-col justify-between">
            <div className="space-y-4">
              <div>
                <label className="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-500 block mb-1.5">
                  1. Dokumen yang Dipindahkan:
                </label>

                {docList.length === 1 ? (
                  <div className="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1.5">
                    <div className="flex items-center justify-between">
                      <span className="font-mono font-black text-sm text-sky-700">
                        {currentDoc.nomor_pb}
                      </span>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        {currentDoc.status}
                      </span>
                    </div>
                    <div className="text-xs font-semibold text-slate-800">{currentDoc.nama_pemohon}</div>
                    <div className="text-[11px] text-slate-500">{currentDoc.bagian}</div>
                  </div>
                ) : (
                  <div className="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2">
                    <div className="font-bold text-xs text-slate-800">
                      Total: {docList.length} Berkas PB Dipilih
                    </div>
                    <div className="max-h-28 overflow-y-auto space-y-1 pr-1">
                      {docList.map((d) => (
                        <div key={d.id} className="text-[11px] font-mono text-sky-700 truncate">
                          • {d.nomor_pb} ({d.nama_pemohon})
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Lokasi Asal Saat Ini */}
              <div className="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2">
                <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-500 block">
                  Lokasi Fisik Asal:
                </span>
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                    <Building2 className="w-4 h-4" />
                  </div>
                  <div>
                    <div className="text-xs font-bold text-slate-900">
                      {currentDoc.lokasi_rak || currentDoc.lokasi_rak_formatted || '-'} • {currentDoc.nomor_box || currentDoc.nomor_box_formatted || '-'}
                    </div>
                    <div className="text-[10px] text-slate-500">Posisi penyimpanan saat ini</div>
                  </div>
                </div>
              </div>

              {/* Syarat Keamanan Mutasi */}
              <div className="p-3 rounded-2xl bg-sky-50 border border-sky-200 text-sky-950 text-xs space-y-1">
                <div className="font-bold flex items-center gap-1.5 text-sky-800">
                  <ShieldAlert className="w-4 h-4" />
                  <span>Integritas Audit Fisik</span>
                </div>
                <p className="text-[11px] text-sky-900 leading-relaxed">
                  Relokasi otomatis memperbarui counter kapasitas kedua lemari rak dan box, serta mencatat Berita Acara Relokasi (BAR) ke dalam Audit Trail.
                </p>
              </div>
            </div>

            <div className="text-[10px] font-mono text-slate-400">
              PT BMS Central Warehouse • Mutasi Spasial 2D
            </div>
          </div>

          {/* RIGHT COLUMN (7 Cols): Pemilih Rak, Box Tujuan, dan Alasan BAR */}
          <div className="lg:col-span-7 p-5 sm:p-6 space-y-4 flex flex-col justify-between">
            <div className="space-y-4">
              {/* 2. Pilih Rak Tujuan */}
              <div>
                <label className="text-[11px] font-black uppercase tracking-wider text-slate-700 block mb-2">
                  2. Pilih Lemari Rak Tujuan:
                </label>

                <div className="flex items-center gap-2 flex-wrap">
                  {racks.map((r) => {
                    const isSelected = r.id === selectedRack?.id;
                    return (
                      <button
                        key={r.id}
                        type="button"
                        onClick={() => {
                          setSelectedRackId(r.id);
                          const firstValid = r.boxes?.find((b) => b.id !== currentBoxId && b.status !== 'Penuh (Terkunci)' && b.sisa_kapasitas >= docList.length);
                          setSelectedBoxId(firstValid ? firstValid.id : (r.boxes?.[0]?.id ?? null));
                        }}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all border ${
                          isSelected
                            ? 'bg-slate-900 text-white border-slate-900 shadow-md'
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                        }`}
                      >
                        <span>{r.kode_rak}</span>
                        <span className="ml-1 opacity-70 font-mono text-[10px]">
                          ({r.persentase_kapasitas}%)
                        </span>
                      </button>
                    );
                  })}
                </div>
                <p className="text-[11px] text-slate-500 mt-1">
                  Zona: {selectedRack?.zona_lokasi} — {selectedRack?.nama_rak}
                </p>
              </div>

              {/* 3. Pilih Box Tujuan */}
              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="text-[11px] font-black uppercase tracking-wider text-slate-700">
                    3. Pilih Ordner Box Tujuan:
                  </label>
                  <span className="text-[10px] text-slate-500">
                    Butuh kuota: {docList.length} Map
                  </span>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-36 overflow-y-auto pr-1">
                  {availableBoxes.map((b) => {
                    const isCurrent = b.id === currentBoxId;
                    const isSelected = b.id === selectedBoxId;
                    const isLocked = b.status === 'Penuh (Terkunci)';
                    const hasCapacity = b.sisa_kapasitas >= docList.length;
                    const isDisabled = isCurrent || isLocked || !hasCapacity;

                    return (
                      <button
                        key={b.id}
                        type="button"
                        disabled={isDisabled}
                        onClick={() => setSelectedBoxId(b.id)}
                        className={`p-3 rounded-2xl text-left border-2 transition-all relative ${
                          isDisabled
                            ? 'opacity-40 bg-slate-100 border-slate-200 cursor-not-allowed'
                            : isSelected
                            ? 'bg-sky-50/80 border-sky-500 shadow-sm ring-2 ring-sky-500/20'
                            : 'bg-white border-slate-200 hover:border-slate-300'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono font-bold text-xs text-slate-900">
                            {b.nomor_box}
                          </span>
                          {isCurrent ? (
                            <span className="text-[9px] font-bold text-slate-400">Asal</span>
                          ) : isLocked ? (
                            <Lock className="w-3 h-3 text-rose-500" />
                          ) : (
                            <span className="text-[10px] font-mono text-slate-500">
                              {b.sisa_kapasitas} Slot
                            </span>
                          )}
                        </div>

                        <div className="text-[10px] text-slate-500 mt-1">
                          B{b.baris_ke} K{b.kolom_ke} • {b.total_dokumen}/{b.kapasitas_maksimal} Map
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* 4. Berita Acara Relokasi (BAR) */}
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <label className="text-[11px] font-black uppercase tracking-wider text-slate-700">
                    4. Berita Acara Relokasi (Alasan Mutasi):
                  </label>
                  <span className="text-[10px] text-slate-400">Klik preset cepat:</span>
                </div>

                <div className="flex flex-wrap gap-1 mb-1.5">
                  {quickPresets.map((p, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => handleApplyPreset(p)}
                      className="text-[10px] px-2 py-0.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-sky-50 hover:border-sky-300 text-slate-600 font-medium"
                    >
                      + {p}
                    </button>
                  ))}
                </div>

                <input
                  type="text"
                  required
                  value={alasan}
                  onChange={(e) => setAlasan(e.target.value)}
                  placeholder="Contoh: Reorganisasi berkas tahun buku 2026 ke lemari Rak B..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                />
              </div>

              {/* Status Readiness Banner */}
              <div
                className={`p-2.5 rounded-xl border flex items-center justify-between text-xs ${
                  isBoxValid
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
                    : 'bg-amber-50 border-amber-200 text-amber-900'
                }`}
              >
                <div className="flex items-center gap-2">
                  {isBoxValid ? (
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  ) : (
                    <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0" />
                  )}
                  <span className="text-[11px] font-bold">
                    {isBoxValid
                      ? `Siap dipindahkan ke: ${selectedRack?.kode_rak} • ${selectedBox?.nomor_box}`
                      : 'Pilih box tujuan yang valid dengan kuota yang cukup'}
                  </span>
                </div>
              </div>
            </div>

            {/* Bottom Actions */}
            <div className="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
              <button
                type="button"
                onClick={onClose}
                disabled={submitting}
                className="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50"
              >
                Batal
              </button>

              <button
                type="submit"
                disabled={submitting || !isBoxValid || !alasan.trim()}
                className="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-md shadow-sky-600/30 flex items-center gap-2 transition-all disabled:opacity-50"
              >
                <ArrowRightLeft className="w-4 h-4" />
                <span>{submitting ? 'Memindahkan...' : '✓ Sahkan Pemindahan Berkas'}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}
