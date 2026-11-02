import React, { useState } from 'react';
import {
  X,
  CheckCircle,
  AlertTriangle,
  FileCheck,
  ClipboardCheck,
  ShieldAlert,
} from 'lucide-react';

export default function ReturnModal({ data, onClose, onSubmit, isSubmitting }) {
  if (!data) return null;

  const [returnDate, setReturnDate] = useState(new Date().toISOString().split('T')[0]);
  const [isFormPbLengkap, setIsFormPbLengkap] = useState(true);
  const [isLampiranLengkap, setIsLampiranLengkap] = useState(true);
  const [kondisiFisik, setKondisiFisik] = useState('Baik & Utuh');
  const [notes, setNotes] = useState('');

  const isProblematic = !isFormPbLengkap || !isLampiranLengkap || kondisiFisik !== 'Baik & Utuh';

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit({
      tanggal_kembali: returnDate,
      is_form_pb_lengkap: isFormPbLengkap,
      is_lampiran_lengkap: isLampiranLengkap,
      kondisi_fisik: kondisiFisik,
      catatan_kembali: notes,
    });
  };

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div className="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        {/* Header */}
        <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center gap-2.5">
            <ClipboardCheck className="w-5 h-5 text-emerald-400" />
            <div>
              <h3 className="text-sm font-bold text-white">BAP Verifikasi & Penerimaan Berkas Fisik</h3>
              <p className="text-[10px] text-slate-400">Pemeriksaan fisik dokumen sebelum dikembalikan ke ordner box</p>
            </div>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-white">
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {/* Summary Box */}
          <div className="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-1.5 text-xs">
            <div className="flex items-center justify-between">
              <span className="text-slate-500 font-bold uppercase text-[10px]">Nomor PB:</span>
              <span className="font-mono font-black text-sky-800 text-sm">{data.nomor_pb}</span>
            </div>
            <div className="flex items-center justify-between text-slate-700">
              <span>Peminjam:</span>
              <strong>{data.nama_peminjam} ({data.departemen_peminjam})</strong>
            </div>
            <div className="flex items-center justify-between text-slate-600 text-[11px] pt-1 border-t border-slate-200/60">
              <span>Lokasi Arsip:</span>
              <span className="font-semibold text-slate-800">{data.lokasi || 'Rak Arsip'}</span>
            </div>
          </div>

          {/* Tanggal Kembali */}
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Tanggal Pengembalian Aktual <span className="text-rose-500">*</span>
            </label>
            <input
              type="date"
              required
              value={returnDate}
              onChange={(e) => setReturnDate(e.target.value)}
              className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
          </div>

          {/* Ceklis Kelengkapan Berkas Fisik */}
          <div className="space-y-2 pt-1">
            <label className="block text-xs font-bold text-slate-800">
              Ceklis Kelengkapan Lembar Fisik <span className="text-rose-500">*</span>
            </label>

            <div className="space-y-2">
              <label
                className={`p-3 rounded-xl border flex items-start gap-3 cursor-pointer transition-all ${
                  isFormPbLengkap
                    ? 'border-emerald-300 bg-emerald-50/60'
                    : 'border-rose-300 bg-rose-50/60'
                }`}
              >
                <input
                  type="checkbox"
                  checked={isFormPbLengkap}
                  onChange={(e) => setIsFormPbLengkap(e.target.checked)}
                  className="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                />
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Formulir Scan PB Asli Tersedia & Utuh
                  </div>
                  <div className="text-[10px] text-slate-500">
                    Lembar formulir asli PB berstempel dan tanda tangan lengkap ada di berkas.
                  </div>
                </div>
              </label>

              <label
                className={`p-3 rounded-xl border flex items-start gap-3 cursor-pointer transition-all ${
                  isLampiranLengkap
                    ? 'border-emerald-300 bg-emerald-50/60'
                    : 'border-rose-300 bg-rose-50/60'
                }`}
              >
                <input
                  type="checkbox"
                  checked={isLampiranLengkap}
                  onChange={(e) => setIsLampiranLengkap(e.target.checked)}
                  className="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                />
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Dokumen Lampiran Lengkap (PO, DO, BAST, Sertifikat)
                  </div>
                  <div className="text-[10px] text-slate-500">
                    Seluruh berkas dokumen pendukung lengkap dan tidak ada yang tercecer/hilang.
                  </div>
                </div>
              </label>
            </div>
          </div>

          {/* Kondisi Fisik Berkas */}
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1.5">
              Kondisi Fisik Dokumen & Map Ordner
            </label>
            <div className="grid grid-cols-3 gap-2">
              {[
                { id: 'Baik & Utuh', desc: 'Bersih & Rapih' },
                { id: 'Rusak Ringan/Robek', desc: 'Ada Robek / Basah' },
                { id: 'Ada Lembar Hilang', desc: 'Halaman Hilang' },
              ].map((k) => (
                <button
                  key={k.id}
                  type="button"
                  onClick={() => setKondisiFisik(k.id)}
                  className={`p-2 rounded-xl border text-center transition-all ${
                    kondisiFisik === k.id
                      ? k.id === 'Baik & Utuh'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-bold ring-1 ring-emerald-600'
                        : k.id === 'Rusak Ringan/Robek'
                        ? 'border-amber-600 bg-amber-50 text-amber-900 font-bold ring-1 ring-amber-600'
                        : 'border-rose-600 bg-rose-50 text-rose-900 font-bold ring-1 ring-rose-600'
                      : 'border-slate-200 hover:border-slate-300 bg-white text-slate-600'
                  }`}
                >
                  <div className="text-[11px] font-bold">{k.id}</div>
                  <div className="text-[9px] text-slate-400 mt-0.5">{k.desc}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Warning banner if problematic */}
          {isProblematic && (
            <div className="p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-start gap-2.5">
              <ShieldAlert className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <strong>Perhatian Petugas Gudang:</strong>
                <p className="text-[11px] mt-0.5">
                  Anda menandai berkas ada yang tidak lengkap atau mengalami kerusakan. Status ini akan tercatat dalam <strong>Berita Acara & Audit Trail</strong> sistem untuk kebutuhan verifikasi.
                </p>
              </div>
            </div>
          )}

          {/* Catatan Tambahan */}
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Catatan Pemeriksaan / Berita Acara Penerimaan
            </label>
            <textarea
              rows={2}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Contoh: Berkas fisik telah diperiksa dan dikembalikan ke ordner Box 02 Rak A."
              className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
          </div>

          <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className={`px-5 py-2 rounded-xl text-xs font-bold text-white shadow-md transition-all ${
                isProblematic
                  ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-600/30'
                  : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30'
              }`}
            >
              {isSubmitting ? 'Memproses BAP...' : '✓ Konfirmasi & Arsipkan Kembali'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
