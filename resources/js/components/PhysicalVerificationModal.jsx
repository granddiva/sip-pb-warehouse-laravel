import React, { useState, useEffect } from 'react';
import {
  X,
  ShieldCheck,
  CheckCircle2,
  AlertTriangle,
  Layers,
  FileCheck2,
  Building2,
  User,
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

  const [racksData, setRacksData] = useState([]);
  const [lokasiRak, setLokasiRak] = useState(doc.lokasi_rak && doc.lokasi_rak !== '-' ? doc.lokasi_rak : 'Rak A');
  const [nomorBox, setNomorBox] = useState(doc.nomor_box && doc.nomor_box !== '-' ? doc.nomor_box : 'Box 01');

  // Inspection Checklist
  const [cekTandaTangan, setCekTandaTangan] = useState(true);
  const [cekLampiranLengkap, setCekLampiranLengkap] = useState(true);
  const [cekKesesuaianItem, setCekKesesuaianItem] = useState(true);

  // Kondisi Berkas
  const [kondisiBerkasFisik, setKondisiBerkasFisik] = useState('Asli & Cap Basah Lengkap');
  const [catatanVerifikasi, setCatatanVerifikasi] = useState('');
  const [submitting, setSubmitting] = useState(false);

  // Load racks
  useEffect(() => {
    rackApi.getRacks().then((res) => {
      setRacksData(res.data || []);
    }).catch(console.error);
  }, []);

  const currentRack = racksData.find((r) => r.kode_rak === lokasiRak);
  const availableBoxes = currentRack?.boxes || [];
  const selectedBox = availableBoxes.find((b) => b.nomor_box === nomorBox);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!cekTandaTangan) {
      onToast('Tanda tangan basah dan cap stempel resmi wajib diperiksa sebelum pengesahan!', 'warning');
      return;
    }

    if (selectedBox?.status === 'Penuh (Terkunci)') {
      onToast(`Box ${selectedBox.nomor_box} sedang dikunci/penuh. Pilih box lain.`, 'warning');
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

      onToast(res.data.message || 'Dokumen berhasil diverifikasi fisik dan diarsipkan.', 'success');
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

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div className="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
        {/* Header */}
        <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 flex-shrink-0">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-sm font-black text-white">Verifikasi Fisik & Penempatan Rak Gudang</h3>
              <p className="text-[10px] text-slate-400">Pengesahan dokumen dari "Menunggu Arsip Fisik" ke "Terverifikasi & Diarsipkan"</p>
            </div>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-white">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scrollable Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4 overflow-y-auto flex-1">
          {/* Document Summary Card */}
          <div className="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2 text-xs">
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dokumen Diperiksa:</span>
              <span className="font-mono font-black text-sky-800 text-sm">{doc.nomor_pb}</span>
            </div>
            <div className="grid grid-cols-2 gap-2 text-slate-700 pt-1">
              <div>
                <span className="text-[10px] text-slate-400 block font-semibold">Departemen Pemohon</span>
                <span className="font-bold">{doc.bagian}</span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 block font-semibold">Pemohon Barang</span>
                <span className="font-bold">{doc.nama_pemohon}</span>
              </div>
            </div>
          </div>

          {/* Checklist Verifikasi */}
          <div className="space-y-2">
            <label className="block text-xs font-bold text-slate-800">
              1. Ceklis Keabsahan Lembar Fisik <span className="text-rose-500">*</span>
            </label>

            <div className="space-y-2">
              <label className="p-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 flex items-start gap-3 cursor-pointer transition-all">
                <input
                  type="checkbox"
                  required
                  checked={cekTandaTangan}
                  onChange={(e) => setCekTandaTangan(e.target.checked)}
                  className="mt-0.5 rounded text-sky-600 focus:ring-sky-500 w-4 h-4"
                />
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Formulir PB Asli Memiliki Tanda Tangan Basah & Cap PT Besmindo
                  </div>
                  <div className="text-[10px] text-slate-500">
                    Wajib ada tanda tangan pemohon, atasan/manager, serta stempel basah resmi.
                  </div>
                </div>
              </label>

              <label className="p-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 flex items-start gap-3 cursor-pointer transition-all">
                <input
                  type="checkbox"
                  checked={cekLampiranLengkap}
                  onChange={(e) => setCekLampiranLengkap(e.target.checked)}
                  className="mt-0.5 rounded text-sky-600 focus:ring-sky-500 w-4 h-4"
                />
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Dokumen Pendukung Fisik Lengkap (PO, DO, BAST, Sertifikat)
                  </div>
                  <div className="text-[10px] text-slate-500">
                    Seluruh lembar lampiran terlampir lengkap di dalam map berkas.
                  </div>
                </div>
              </label>

              <label className="p-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 flex items-start gap-3 cursor-pointer transition-all">
                <input
                  type="checkbox"
                  checked={cekKesesuaianItem}
                  onChange={(e) => setCekKesesuaianItem(e.target.checked)}
                  className="mt-0.5 rounded text-sky-600 focus:ring-sky-500 w-4 h-4"
                />
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Kesesuaian Item Material dengan Data di Sistem
                  </div>
                  <div className="text-[10px] text-slate-500">
                    Kuantitas, part number, dan deskripsi di lembar kertas cocok dengan data aplikasi.
                  </div>
                </div>
              </label>
            </div>
          </div>

          {/* Kondisi Berkas Fisik */}
          <div>
            <label className="block text-xs font-bold text-slate-800 mb-1.5">
              2. Kondisi Fisik Dokumen Hardcopy <span className="text-rose-500">*</span>
            </label>
            <div className="grid grid-cols-3 gap-2">
              {[
                { id: 'Asli & Cap Basah Lengkap', label: 'Asli & Cap Basah', desc: 'Sesuai Standar SOP' },
                { id: 'Salinan Resmi Terlegalisir', label: 'Salinan Legal', desc: 'Cap Salinan Sah' },
                { id: 'Sebagian Dokumen Asli', label: 'Sebagian Asli', desc: 'Ada Lembar Foto' },
              ].map((k) => (
                <button
                  key={k.id}
                  type="button"
                  onClick={() => setKondisiBerkasFisik(k.id)}
                  className={`p-2.5 rounded-xl border text-center transition-all ${
                    kondisiBerkasFisik === k.id
                      ? 'border-sky-600 bg-sky-50 text-sky-950 font-bold ring-1 ring-sky-600'
                      : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'
                  }`}
                >
                  <div className="text-xs font-bold">{k.label}</div>
                  <div className="text-[9px] text-slate-400 mt-0.5">{k.desc}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Alokasi Penempatan Rak & Box Fisik */}
          <div className="space-y-2 pt-1 border-t border-slate-100">
            <label className="block text-xs font-bold text-slate-800">
              3. Konfirmasi Penempatan Rak & Box Gudang <span className="text-rose-500">*</span>
            </label>

            {/* Rak Tabs */}
            <div className="grid grid-cols-4 gap-2">
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
                    className={`p-2 rounded-xl border text-center transition-all ${
                      isSelected
                        ? 'border-sky-600 bg-sky-50 font-bold text-sky-950 ring-1 ring-sky-600'
                        : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'
                    }`}
                  >
                    <div className="text-xs font-black">{r.kode_rak}</div>
                    <div className="text-[10px] text-slate-400">{r.persentase_kapasitas}%</div>
                  </button>
                );
              })}
            </div>

            {/* Box Grid */}
            <div className="grid grid-cols-2 gap-2 max-h-36 overflow-y-auto pr-1 pt-1">
              {availableBoxes.map((b) => {
                const isSelected = b.nomor_box === nomorBox;
                const isLocked = b.status === 'Penuh (Terkunci)';

                return (
                  <div
                    key={b.id}
                    onClick={() => {
                      if (!isLocked) setNomorBox(b.nomor_box);
                    }}
                    className={`p-2.5 rounded-xl border text-xs transition-all ${
                      isLocked
                        ? 'border-rose-200 bg-rose-50/50 opacity-60 cursor-not-allowed'
                        : isSelected
                        ? 'border-sky-600 bg-sky-50 ring-2 ring-sky-500/20 cursor-pointer'
                        : 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer'
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-mono font-bold text-slate-900">{b.nomor_box}</span>
                      <span className={`text-[9px] font-bold px-1.5 py-0.2 rounded ${
                        isLocked ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700'
                      }`}>
                        {isLocked ? '🔒 Terkunci' : `${b.total_dokumen}/${b.kapasitas_maksimal}`}
                      </span>
                    </div>

                    <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                      <div
                        className={`h-full rounded-full ${
                          isLocked ? 'bg-rose-600' : (b.persentase_keterisian || 0) >= 90 ? 'bg-rose-500' : 'bg-emerald-500'
                        }`}
                        style={{ width: `${isLocked ? 100 : Math.min(b.persentase_keterisian || 0, 100)}%` }}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Catatan Verifikasi */}
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Catatan Berita Acara Penerimaan (BAP)
            </label>
            <textarea
              rows={2}
              value={catatanVerifikasi}
              onChange={(e) => setCatatanVerifikasi(e.target.value)}
              placeholder="Contoh: Berkas fisik hardcopy telah diterima lengkap dengan cap basah dan disimpan pada Box 01 Rak A."
              className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
          </div>

          {/* Actions */}
          <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={onClose}
              disabled={submitting}
              className="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={submitting}
              className="px-6 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-lg shadow-sky-600/30 flex items-center gap-2 transition-all disabled:opacity-50"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>{submitting ? 'Mengesahkan...' : '✓ Sahkan & Simpan ke Rak Gudang'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
