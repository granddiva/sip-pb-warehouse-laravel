import React, { useState, useEffect } from 'react';
import { X, Clock, AlertTriangle } from 'lucide-react';
import { borrowingApi } from '../api';

export default function BorrowModal({ document: initialDoc, onClose, onSuccess, onToast }) {
  const [availableDocs, setAvailableDocs] = useState([]);
  const [selectedDocId, setSelectedDocId] = useState(initialDoc?.id || '');
  const [borrowerName, setBorrowerName] = useState('');
  const [department, setDepartment] = useState('');
  const [purpose, setPurpose] = useState('');
  const [borrowDate, setBorrowDate] = useState(new Date().toISOString().split('T')[0]);
  const [dueDate, setDueDate] = useState(() => {
    const d = new Date();
    d.setDate(d.getDate() + 7);
    return d.toISOString().split('T')[0];
  });
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!initialDoc) {
      borrowingApi.getAvailableDocs().then((res) => {
        setAvailableDocs(res.data);
      }).catch(console.error);
    }
  }, [initialDoc]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    const docId = initialDoc?.id || selectedDocId;
    if (!docId) {
      onToast('Pilih dokumen yang ingin dipinjam terlebih dahulu.', 'warning');
      return;
    }

    setLoading(true);
    try {
      const res = await borrowingApi.borrow({
        document_id: docId,
        nama_peminjam: borrowerName,
        departemen_peminjam: department,
        keperluan: purpose,
        tanggal_pinjam: borrowDate,
        batas_kembali: dueDate,
        catatan: notes,
      });

      onToast(res.data.message || 'Peminjaman berhasil dicatat.', 'success');
      onSuccess();
      onClose();
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memproses peminjaman dokumen.';
      onToast(msg, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center gap-2.5">
            <Clock className="w-5 h-5 text-amber-400" />
            <h3 className="text-sm font-bold text-white">Catat Peminjaman Berkas Fisik</h3>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-white">
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
          {initialDoc ? (
            <div className="bg-sky-50 border border-sky-200 rounded-xl p-3.5 space-y-1 text-xs">
              <div className="text-sky-700 font-semibold">Dokumen yang Dipinjam:</div>
              <div className="font-mono font-bold text-sky-950 text-sm">{initialDoc.nomor_pb}</div>
              <div className="text-sky-800">
                Departemen: {initialDoc.bagian} | Lokasi: {initialDoc.lokasi_rak} - {initialDoc.nomor_box}
              </div>
            </div>
          ) : (
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Pilih Dokumen PB <span className="text-rose-500">*</span>
              </label>
              <select
                required
                value={selectedDocId}
                onChange={(e) => setSelectedDocId(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
              >
                <option value="">-- Pilih Dokumen yang Tersedia di Gudang --</option>
                {availableDocs.map((doc) => (
                  <option key={doc.id} value={doc.id}>
                    {doc.nomor_pb} | {doc.bagian} | ({doc.nama_pemohon})
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Nama Peminjam <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                required
                value={borrowerName}
                onChange={(e) => setBorrowerName(e.target.value)}
                placeholder="Contoh: Dedi Suhendar, S.T."
                className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Departemen / Divisi <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                required
                value={department}
                onChange={(e) => setDepartment(e.target.value)}
                placeholder="Contoh: NDT / Inspeksi"
                className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Keperluan Peminjaman <span className="text-rose-500">*</span>
            </label>
            <textarea
              required
              rows={2}
              value={purpose}
              onChange={(e) => setPurpose(e.target.value)}
              placeholder="Contoh: Audit mutu internal dan pemeriksaan sertifikat kalibrasi."
              className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Tanggal Pinjam <span className="text-rose-500">*</span>
              </label>
              <input
                type="date"
                required
                value={borrowDate}
                onChange={(e) => setBorrowDate(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Batas Pengembalian (Due Date) <span className="text-rose-500">*</span>
              </label>
              <input
                type="date"
                required
                value={dueDate}
                onChange={(e) => setDueDate(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Catatan Kondisi Fisik (Opsional)
            </label>
            <textarea
              rows={2}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Catatan kondisi ordner, nota terlampir, atau nomor kontak peminjam."
              className="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
          </div>

          <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={onClose}
              disabled={loading}
              className="px-4 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-5 py-2 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-sm shadow-amber-600/30 transition-colors"
            >
              {loading ? 'Menyimpan...' : '✓ Catat Peminjaman'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
