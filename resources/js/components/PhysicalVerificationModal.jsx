import React, { useState, useEffect } from 'react';
import {
  X,
  ShieldCheck,
  CheckCircle2,
  AlertTriangle,
  RotateCcw,
  Stamp,
  Paperclip,
  Check,
  FileSpreadsheet,
  Lock,
} from 'lucide-react';
import { documentApi, rackApi } from '../api';

export default function PhysicalVerificationModal({
  document: doc,
  onClose,
  onSuccess,
  onToast,
}) {
  if (!doc) return null;

  // Verification mode: 'approve' (Terima & Simpan ke Rak) vs 'reject' (Kembalikan / Perlu Revisi)
  const [mode, setMode] = useState('approve');

  // Rack & Box states (for approve mode)
  const [racksData, setRacksData] = useState([]);
  const [lokasiRak, setLokasiRak] = useState(
    doc.lokasi_rak && doc.lokasi_rak !== '-' ? doc.lokasi_rak : 'Rak A'
  );
  const [nomorBox, setNomorBox] = useState(
    doc.nomor_box && doc.nomor_box !== '-' ? doc.nomor_box : 'Box 01'
  );

  // 3-Point Verification Checklist (Approve mode)
  const [cekTandaTangan, setCekTandaTangan] = useState(true);
  const [cekLampiranLengkap, setCekLampiranLengkap] = useState(true);
  const [cekKesesuaianItem, setCekKesesuaianItem] = useState(true);
  const [kondisiBerkasFisik, setKondisiBerkasFisik] = useState('Asli & Cap Basah Lengkap');
  const [catatanVerifikasi, setCatatanVerifikasi] = useState('');

  // Rejection / Revision state (Reject mode)
  const [alasanPenolakan, setAlasanPenolakan] = useState('');

  const [submitting, setSubmitting] = useState(false);

  // Preset chips for Rejection Mode (as specified)
  const rejectionPresetChips = [
    'Tanda tangan/cap basah belum lengkap',
    'Lampiran PO/DO/BAST kurang',
    'Sertifikat MTC belum terlampir',
    'Fisik part tidak sesuai PB',
  ];

  // Quick preset notes for Approve Mode
  const quickNotesApprove = [
    'Berkas asli lengkap & cap basah sah',
    'Lampiran PO/DO/BAST lengkap terarsip',
    'Sesuai spesifikasi part gudang',
  ];

  // Load racks
  useEffect(() => {
    rackApi.getRacks().then((res) => {
      setRacksData(res.data || []);
    }).catch(console.error);
  }, []);

  const currentRack = racksData.find((r) => r.kode_rak === lokasiRak);
  const availableBoxes = currentRack?.boxes || [];
  const selectedBox = availableBoxes.find((b) => b.nomor_box === nomorBox);

  // Check if fully compliant for approval
  const isFullyCompliant = cekTandaTangan && cekLampiranLengkap && cekKesesuaianItem;

  const handleAppendApproveNote = (note) => {
    setCatatanVerifikasi((prev) => (prev ? `${prev}. ${note}` : note));
  };

  const handleAppendRejectionChip = (chipText) => {
    setAlasanPenolakan((prev) => {
      if (!prev.trim()) return chipText;
      if (prev.includes(chipText)) return prev;
      return `${prev}; ${chipText}`;
    });
  };

  // Submit Approval
  const handleApproveSubmit = async (e) => {
    e.preventDefault();

    if (!cekTandaTangan) {
      onToast('Tanda tangan basah dan cap stempel resmi wajib diperiksa sebelum pengesahan!', 'warning');
      return;
    }

    if (selectedBox?.status === 'Penuh (Terkunci)') {
      onToast(`Box ${selectedBox.nomor_box} sedang dikunci/penuh secara fisik. Pilih box lain.`, 'warning');
      return;
    }

    setSubmitting(true);
    try {
      const res = await documentApi.verifyPhysical(doc.id, {
        lokasi_rak: lokasiRak,
        nomor_box: nomorBox,
        kondisi_berkas_fisik: kondisiBerkasFisik,
        cek_tanda_tangan: cekTandaTangan,
        cek_lampiran_lengkap: cekLampiranLengkap,
        catatan_verifikasi: catatanVerifikasi,
      });

      onToast(res.data.message || 'Dokumen fisik berhasil diverifikasi dan diarsipkan.', 'success');
      onSuccess(res.data.document);
      onClose();
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.message || 'Gagal memverifikasi dokumen fisik.';
      onToast(msg, 'error');
    } finally {
      setSubmitting(false);
    }
  };

  // Submit Rejection / Return for Revision
  const handleRejectSubmit = async (e) => {
    e.preventDefault();

    if (!alasanPenolakan.trim()) {
      onToast('Alasan pengembalian/revisi berkas fisik wajib diisi atau pilih preset chip!', 'warning');
      return;
    }

    setSubmitting(true);
    try {
      const res = await documentApi.rejectPhysical(doc.id, {
        alasan_penolakan: alasanPenolakan.trim(),
      });

      onToast(res.data.message || 'Dokumen berhasil dikembalikan dengan status Perlu Revisi Fisik.', 'info');
      onSuccess(res.data.document);
      onClose();
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.message || 'Gagal memproses penolakan berkas fisik.';
      onToast(msg, 'error');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
      <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 overflow-hidden flex flex-col max-h-[92vh]">
        {/* Top Header Bar */}
        <div className="px-6 py-3 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center gap-3">
            <div className={`w-8 h-8 rounded-xl flex items-center justify-center transition-colors ${
              mode === 'approve'
                ? 'bg-sky-500/20 text-sky-400'
                : 'bg-rose-500/20 text-rose-400'
            }`}>
              {mode === 'approve' ? (
                <ShieldCheck className="w-5 h-5" />
              ) : (
                <RotateCcw className="w-5 h-5" />
              )}
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm font-black text-white tracking-tight">
                  {mode === 'approve'
                    ? 'Verifikasi Fisik & Penempatan Rak Gudang'
                    : 'Penolakan / Pengembalian Berkas Fisik PB'}
                </h3>
                <span className="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-black uppercase tracking-wider">
                  Menunggu Fisik
                </span>
              </div>
              <p className="text-[11px] text-slate-400">
                SOP Pengesahan & Audit Berkas Hardcopy PT Besmindo Materi Sewatama
              </p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Mode Toggle Bar (Segmented Switcher) */}
        <div className="px-6 py-2.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
          <span className="text-[11px] font-black text-slate-500 uppercase tracking-wider">
            Tindakan Petugas:
          </span>
          <div className="flex rounded-xl bg-slate-200/90 p-1 w-full max-w-sm">
            <button
              type="button"
              onClick={() => setMode('approve')}
              className={`flex-1 py-1.5 px-3 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5 ${
                mode === 'approve'
                  ? 'bg-white text-emerald-800 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
              <span>Terima & Simpan ke Rak</span>
            </button>
            <button
              type="button"
              onClick={() => setMode('reject')}
              className={`flex-1 py-1.5 px-3 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5 ${
                mode === 'reject'
                  ? 'bg-white text-rose-800 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <RotateCcw className="w-3.5 h-3.5 text-rose-600" />
              <span>Kembalikan / Perlu Revisi</span>
            </button>
          </div>
        </div>

        {/* 2-Column Responsive Split Canvas (Zero Internal Scrollbar for 1366x768 screens!) */}
        {mode === 'approve' ? (
          <form onSubmit={handleApproveSubmit} className="p-4 sm:p-5 grid grid-cols-1 md:grid-cols-12 gap-4">
            {/* LEFT COLUMN (5 Cols): Ringkasan Dokumen & Ceklis Keabsahan */}
            <div className="md:col-span-5 space-y-3 flex flex-col justify-between">
              {/* Identity Card */}
              <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-1.5">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    Nomor PB
                  </span>
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-sky-100 text-sky-800 truncate max-w-[150px]">
                    {doc.bagian}
                  </span>
                </div>
                <div className="font-mono font-black text-sm text-sky-950 break-all leading-tight">
                  {doc.nomor_pb}
                </div>
                <div className="text-[11px] text-slate-600 flex items-center justify-between pt-1 border-t border-slate-200">
                  <span>Pemohon:</span>
                  <strong className="text-slate-900">{doc.nama_pemohon}</strong>
                </div>
              </div>

              {/* 3-Point Verification Checklist */}
              <div className="space-y-1.5">
                <label className="block text-[10px] font-black uppercase tracking-wider text-slate-700">
                  1. Ceklis Keabsahan Berkas Hardcopy:
                </label>

                {/* Item 1 */}
                <div
                  onClick={() => setCekTandaTangan(!cekTandaTangan)}
                  className={`p-2 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs ${
                    cekTandaTangan
                      ? 'border-emerald-300 bg-emerald-50/70 text-emerald-950 ring-1 ring-emerald-500/30'
                      : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <Stamp className={`w-4 h-4 flex-shrink-0 ${cekTandaTangan ? 'text-emerald-600' : 'text-slate-400'}`} />
                    <div>
                      <div className="font-bold text-[11px] leading-tight">Tanda Tangan & Cap Basah PT BMS</div>
                      <div className="text-[10px] text-slate-500">Pemohon, atasan & stempel resmi ada</div>
                    </div>
                  </div>
                  <div className={`w-4 h-4 rounded-md flex items-center justify-center flex-shrink-0 ${
                    cekTandaTangan ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-white'
                  }`}>
                    {cekTandaTangan && <Check className="w-3 h-3 stroke-[3]" />}
                  </div>
                </div>

                {/* Item 2 */}
                <div
                  onClick={() => setCekLampiranLengkap(!cekLampiranLengkap)}
                  className={`p-2 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs ${
                    cekLampiranLengkap
                      ? 'border-emerald-300 bg-emerald-50/70 text-emerald-950 ring-1 ring-emerald-500/30'
                      : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <Paperclip className={`w-4 h-4 flex-shrink-0 ${cekLampiranLengkap ? 'text-emerald-600' : 'text-slate-400'}`} />
                    <div>
                      <div className="font-bold text-[11px] leading-tight">Lampiran Fisik Lengkap</div>
                      <div className="text-[10px] text-slate-500">PO, DO, BAST, Sertifikat ada</div>
                    </div>
                  </div>
                  <div className={`w-4 h-4 rounded-md flex items-center justify-center flex-shrink-0 ${
                    cekLampiranLengkap ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-white'
                  }`}>
                    {cekLampiranLengkap && <Check className="w-3 h-3 stroke-[3]" />}
                  </div>
                </div>

                {/* Item 3 */}
                <div
                  onClick={() => setCekKesesuaianItem(!cekKesesuaianItem)}
                  className={`p-2 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs ${
                    cekKesesuaianItem
                      ? 'border-emerald-300 bg-emerald-50/70 text-emerald-950 ring-1 ring-emerald-500/30'
                      : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <FileSpreadsheet className={`w-4 h-4 flex-shrink-0 ${cekKesesuaianItem ? 'text-emerald-600' : 'text-slate-400'}`} />
                    <div>
                      <div className="font-bold text-[11px] leading-tight">Kesesuaian Fisik vs Input Sistem</div>
                      <div className="text-[10px] text-slate-500">Part number & kuantitas cocok</div>
                    </div>
                  </div>
                  <div className={`w-4 h-4 rounded-md flex items-center justify-center flex-shrink-0 ${
                    cekKesesuaianItem ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-white'
                  }`}>
                    {cekKesesuaianItem && <Check className="w-3 h-3 stroke-[3]" />}
                  </div>
                </div>
              </div>

              {/* Kondisi Fisik Berkas */}
              <div>
                <label className="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1">
                  Kondisi Berkas:
                </label>
                <div className="grid grid-cols-3 gap-1.5">
                  {[
                    { id: 'Asli & Cap Basah Lengkap', label: 'Asli & Cap' },
                    { id: 'Salinan Resmi Terlegalisir', label: 'Salinan Sah' },
                    { id: 'Sebagian Dokumen Asli', label: 'Sebagian Asli' },
                  ].map((k) => (
                    <button
                      key={k.id}
                      type="button"
                      onClick={() => setKondisiBerkasFisik(k.id)}
                      className={`py-1 px-1 rounded-xl border text-center text-[10px] font-bold transition-all ${
                        kondisiBerkasFisik === k.id
                          ? 'border-sky-600 bg-sky-50 text-sky-900 ring-1 ring-sky-600'
                          : 'border-slate-200 hover:border-slate-300 bg-white text-slate-600'
                      }`}
                    >
                      {k.label}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            {/* RIGHT COLUMN (7 Cols): Alokasi Rak & Box Gudang + Catatan BAP */}
            <div className="md:col-span-7 space-y-2.5 flex flex-col justify-between">
              {/* Rak Selection */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-[10px] font-black uppercase tracking-wider text-slate-700">
                    2. Alokasi Rak & Ordner Gudang:
                  </label>
                  <span className="text-[10px] font-bold text-slate-400">
                    Zona: {currentRack?.zona_lokasi || 'Warehouse'}
                  </span>
                </div>

                {/* Rak Pills */}
                <div className="grid grid-cols-4 gap-1.5 mb-2">
                  {racksData.map((r) => {
                    const isSelected = r.kode_rak === lokasiRak;
                    return (
                      <button
                        key={r.id}
                        type="button"
                        onClick={() => {
                          setLokasiRak(r.kode_rak);
                          if (r.boxes?.length > 0) {
                            setNomorBox(r.boxes[0].nomor_box);
                          }
                        }}
                        className={`p-1.5 rounded-xl border text-center transition-all ${
                          isSelected
                            ? 'border-sky-600 bg-sky-50 font-bold text-sky-950 ring-1 ring-sky-600 shadow-sm'
                            : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'
                        }`}
                      >
                        <div className="text-xs font-black">{r.kode_rak}</div>
                        <div className="text-[9px] text-slate-400">{r.persentase_kapasitas}% Terisi</div>
                      </button>
                    );
                  })}
                </div>

                {/* Box Selector Grid */}
                <div className="grid grid-cols-2 gap-1.5">
                  {availableBoxes.map((b) => {
                    const isSelected = b.nomor_box === nomorBox;
                    const isLocked = b.status === 'Penuh (Terkunci)';
                    const isFull = (b.persentase_keterisian || 0) >= 90;

                    return (
                      <div
                        key={b.id}
                        onClick={() => {
                          if (!isLocked) setNomorBox(b.nomor_box);
                        }}
                        className={`p-1.5 rounded-xl border transition-all ${
                          isLocked
                            ? 'border-rose-200 bg-rose-50/40 opacity-60 cursor-not-allowed'
                            : isSelected
                            ? 'border-sky-600 bg-sky-50/80 ring-2 ring-sky-500/20 shadow-sm cursor-pointer'
                            : 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer'
                        }`}
                      >
                        <div className="flex items-center justify-between mb-1">
                          <div className="flex items-center gap-1 font-mono font-bold text-xs text-slate-900">
                            {isLocked && <Lock className="w-3 h-3 text-rose-600" />}
                            <span>{b.nomor_box}</span>
                          </div>
                          <span className={`text-[9px] font-bold px-1.5 py-0.2 rounded ${
                            isLocked
                              ? 'bg-rose-600 text-white'
                              : isFull
                              ? 'bg-amber-100 text-amber-800'
                              : 'bg-emerald-100 text-emerald-800'
                          }`}>
                            {isLocked ? 'Penuh' : `${b.total_dokumen}/${b.kapasitas_maksimal}`}
                          </span>
                        </div>

                        <div className="w-full bg-slate-100 h-1 rounded-full overflow-hidden">
                          <div
                            className={`h-full rounded-full transition-all ${
                              isLocked
                                ? 'bg-rose-600'
                                : isFull
                                ? 'bg-amber-500'
                                : 'bg-emerald-500'
                            }`}
                            style={{ width: `${isLocked ? 100 : Math.min(b.persentase_keterisian || 0, 100)}%` }}
                          />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Catatan Verifikasi / BAP */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-[10px] font-black uppercase tracking-wider text-slate-700">
                    3. Berita Acara Penerimaan (BAP):
                  </label>
                  <span className="text-[10px] text-slate-400">Klik preset cepat:</span>
                </div>

                {/* Quick Preset Tags */}
                <div className="flex flex-wrap gap-1 mb-1">
                  {quickNotesApprove.map((qn, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => handleAppendApproveNote(qn)}
                      className="text-[10px] px-2 py-0.5 rounded-md border border-slate-200 bg-slate-50 hover:bg-sky-50 hover:border-sky-300 text-slate-600 hover:text-sky-800 font-medium transition-all"
                    >
                      + {qn.split(' ')[0]} {qn.split(' ')[1]}
                    </button>
                  ))}
                </div>

                <input
                  type="text"
                  value={catatanVerifikasi}
                  onChange={(e) => setCatatanVerifikasi(e.target.value)}
                  placeholder="Catatan kondisi dokumen, stempel, atau nomor ordner fisik..."
                  className="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                />
              </div>

              {/* Readiness Banner */}
              <div className={`p-2 rounded-xl border flex items-center justify-between text-xs ${
                isFullyCompliant
                  ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900'
                  : 'bg-amber-50/80 border-amber-200 text-amber-900'
              }`}>
                <div className="flex items-center gap-1.5">
                  {isFullyCompliant ? (
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  ) : (
                    <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0" />
                  )}
                  <span className="text-[10px] font-bold">
                    {isFullyCompliant
                      ? `Siap diarsipkan ke: ${lokasiRak} • ${nomorBox}`
                      : 'Periksa kelengkapan cap dan tanda tangan sebelum mengesahkan'}
                  </span>
                </div>
              </div>

              {/* Bottom Actions */}
              <div className="flex items-center justify-end gap-2 pt-1">
                <button
                  type="button"
                  onClick={onClose}
                  disabled={submitting}
                  className="px-3.5 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting || !cekTandaTangan}
                  className="px-4 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/30 flex items-center gap-1.5 transition-all disabled:opacity-50"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  <span>{submitting ? 'Mengesahkan...' : '✓ Sahkan & Simpan ke Rak'}</span>
                </button>
              </div>
            </div>
          </form>
        ) : (
          /* REJECTION / RETURN FOR REVISION FORM (Zero-Scroll 2-Column Split View) */
          <form onSubmit={handleRejectSubmit} className="p-4 sm:p-5 grid grid-cols-1 md:grid-cols-12 gap-4">
            {/* LEFT COLUMN (5 Cols): Ringkasan Dokumen & Peringatan Penolakan */}
            <div className="md:col-span-5 space-y-3 flex flex-col justify-between">
              {/* Identity Card */}
              <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-1.5">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    Nomor PB
                  </span>
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-800 truncate max-w-[150px]">
                    {doc.bagian}
                  </span>
                </div>
                <div className="font-mono font-black text-sm text-slate-900 break-all leading-tight">
                  {doc.nomor_pb}
                </div>
                <div className="text-[11px] text-slate-600 flex items-center justify-between pt-1 border-t border-slate-200">
                  <span>Pemohon:</span>
                  <strong className="text-slate-900">{doc.nama_pemohon}</strong>
                </div>
              </div>

              {/* SOP Notice Card */}
              <div className="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200 text-rose-950 space-y-2">
                <div className="flex items-center gap-2 text-rose-700 font-black text-xs">
                  <AlertTriangle className="w-4 h-4 flex-shrink-0" />
                  <span>SOP Pengembalian Berkas Fisik:</span>
                </div>
                <p className="text-[11px] text-rose-900/90 leading-relaxed">
                  Dokumen PB yang dikembalikan akan beralih status ke <strong>"Perlu Revisi Fisik"</strong>. Berkas fisik tidak akan ditempatkan ke rak ordner gudang sebelum pemohon melengkapi kekurangan formulir atau lampiran pendukung.
                </p>
                <div className="p-2 rounded-xl bg-white/80 border border-rose-200 text-[10px] text-rose-800 space-y-1">
                  <div className="font-bold">• Pemohon dapat merevisi data/lampiran</div>
                  <div className="font-bold">• Berkas dapat diajukan ulang ke gudang</div>
                </div>
              </div>

              {/* Status Transition Pill */}
              <div className="p-2.5 rounded-xl border border-amber-300 bg-amber-50 text-amber-950 text-xs flex items-center justify-between font-bold">
                <span className="text-[10px] uppercase text-amber-800">Peralihan Status:</span>
                <span className="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-mono">
                  ➔ Perlu Revisi Fisik
                </span>
              </div>
            </div>

            {/* RIGHT COLUMN (7 Cols): Preset Chips & Alasan Penolakan */}
            <div className="md:col-span-7 space-y-3 flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <label className="text-[11px] font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                    <RotateCcw className="w-3.5 h-3.5 text-rose-600" />
                    <span>Pilih Preset Kekurangan Berkas (Klik Cepat):</span>
                  </label>
                  <span className="text-[10px] text-slate-400">Pilih 1 atau lebih:</span>
                </div>

                {/* Preset Chips (Single Click without manual typing) */}
                <div className="grid grid-cols-2 gap-1.5 mb-2.5">
                  {rejectionPresetChips.map((chip, idx) => {
                    const isIncluded = alasanPenolakan.includes(chip);
                    return (
                      <button
                        key={idx}
                        type="button"
                        onClick={() => handleAppendRejectionChip(chip)}
                        className={`p-2 rounded-xl border text-left text-[11px] font-bold transition-all flex items-start gap-1.5 leading-snug ${
                          isIncluded
                            ? 'border-rose-400 bg-rose-50 text-rose-950 ring-1 ring-rose-400 shadow-sm'
                            : 'border-slate-200 hover:border-rose-300 bg-white text-slate-700 hover:bg-slate-50'
                        }`}
                      >
                        <span className="text-rose-600 font-black flex-shrink-0 mt-0.5">+</span>
                        <span>{chip}</span>
                      </button>
                    );
                  })}
                </div>

                {/* Textarea for detailed / combined instructions */}
                <div>
                  <label className="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1">
                    Instruksi Catatan Revisi untuk Pemohon:
                  </label>
                  <textarea
                    rows={3}
                    value={alasanPenolakan}
                    onChange={(e) => setAlasanPenolakan(e.target.value)}
                    placeholder="Ketik keterangan atau klik preset chips di atas (contoh: Tanda tangan/cap basah belum lengkap; Lampiran PO kurang)..."
                    className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-rose-500 focus:outline-none resize-none bg-white"
                  />
                  <div className="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                    <span>* Catatan ini akan dicatat ke Berita Acara & Audit Log ISO gudang</span>
                    {alasanPenolakan && (
                      <button
                        type="button"
                        onClick={() => setAlasanPenolakan('')}
                        className="text-rose-600 hover:underline font-bold"
                      >
                        Bersihkan Teks
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* Bottom Actions */}
              <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  onClick={onClose}
                  disabled={submitting}
                  className="px-3.5 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting || !alasanPenolakan.trim()}
                  className="px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/30 flex items-center gap-1.5 transition-all disabled:opacity-50"
                >
                  <RotateCcw className="w-4 h-4" />
                  <span>{submitting ? 'Memproses Pengembalian...' : 'Kembalikan / Minta Revisi Berkas'}</span>
                </button>
              </div>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
