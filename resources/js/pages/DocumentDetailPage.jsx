import React, { useState, useEffect } from 'react';
import {
  FileText,
  Clock,
  QrCode,
  Paperclip,
  CheckCircle,
  AlertTriangle,
  Upload,
  Download,
  Eye,
  Trash2,
  ArrowLeft,
  Edit,
  Printer,
  ShieldCheck,
  FileCheck2,
  RotateCcw,
} from 'lucide-react';
import { documentApi, attachmentApi } from '../api';

export default function DocumentDetailPage({
  documentId,
  onNavigate,
  onToast,
  onOpenPreview,
  onOpenReturnModal,
  onOpenBorrowModal,
  onOpenVerificationModal,
  refreshKey,
  onRefresh,
}) {
  const [doc, setDoc] = useState(null);
  const [loading, setLoading] = useState(true);

  // New Attachment form state
  const [showUploadForm, setShowUploadForm] = useState(false);
  const [jenisDokumen, setJenisDokumen] = useState('Form PB Scan Asli');
  const [attachmentFile, setAttachmentFile] = useState(null);
  const [uploading, setUploading] = useState(false);

  const fetchDocument = async () => {
    try {
      setLoading(true);
      const res = await documentApi.getById(documentId);
      setDoc(res.data);
    } catch (err) {
      console.error(err);
      onToast('Gagal memuat detail dokumen.', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (documentId) {
      fetchDocument();
    }
  }, [documentId, refreshKey]);

  const handleUploadAttachment = async (e) => {
    e.preventDefault();
    if (!attachmentFile) {
      onToast('Pilih berkas lampiran terlebih dahulu.', 'warning');
      return;
    }

    const formData = new FormData();
    formData.append('jenis_dokumen', jenisDokumen);
    formData.append('file', attachmentFile);

    setUploading(true);
    try {
      const res = await attachmentApi.upload(documentId, formData);
      onToast(res.data.message || 'Lampiran berhasil diunggah.', 'success');
      setShowUploadForm(false);
      setAttachmentFile(null);
      fetchDocument();
      if (onRefresh) onRefresh();
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal mengunggah lampiran.';
      onToast(msg, 'error');
    } finally {
      setUploading(false);
    }
  };

  const handleDeleteAttachment = async (attachmentId, fileName) => {
    if (!window.confirm(`Hapus lampiran ${fileName}?`)) return;

    try {
      const res = await attachmentApi.delete(attachmentId);
      onToast(res.data.message || 'Lampiran berhasil dihapus.', 'success');
      fetchDocument();
      if (onRefresh) onRefresh();
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal menghapus lampiran.';
      onToast(msg, 'error');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="w-10 h-10 border-4 border-sky-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!doc) {
    return (
      <div className="text-center py-16">
        <p className="text-slate-500 font-bold">Dokumen tidak ditemukan.</p>
        <button
          onClick={() => onNavigate('documents')}
          className="mt-4 px-4 py-2 bg-sky-600 text-white rounded-xl text-xs font-bold"
        >
          Kembali ke Daftar
        </button>
      </div>
    );
  }

  const isBorrowed = doc.status.includes('Dipinjam') && doc.active_borrowing;

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      {/* Top Header & Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <button
            onClick={() => onNavigate('documents')}
            className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-sky-600 mb-2 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>Kembali ke Dokumen PB</span>
          </button>
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">Rincian Dokumen Permintaan Barang</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Nomor PB: <strong className="font-mono text-sky-700">{doc.nomor_pb}</strong>
          </p>
        </div>

        <div className="flex items-center gap-2 flex-wrap">
          {/* Action: Verify Physical */}
          {doc.status.includes('Menunggu') && onOpenVerificationModal && (
            <button
              onClick={() => onOpenVerificationModal(doc)}
              className="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/30 flex items-center gap-1.5 transition-all"
            >
              <ShieldCheck className="w-4 h-4 stroke-[2.5]" />
              <span>Verifikasi Fisik & Sahkan</span>
            </button>
          )}

          {/* Action: Revisi Berkas if status is Perlu Revisi Fisik */}
          {doc.status.includes('Revisi') && (
            <button
              onClick={() => onNavigate('document-edit', doc.id)}
              className="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/30 flex items-center gap-1.5 transition-all"
            >
              <RotateCcw className="w-4 h-4" />
              <span>Perbaiki & Ajukan Ulang</span>
            </button>
          )}

          {/* Action: Borrow or Return */}
          {isBorrowed ? (
            <button
              onClick={() => onOpenReturnModal(doc.active_borrowing.id, doc.nomor_pb, doc.active_borrowing.nama_peminjam)}
              className="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-600/30 flex items-center gap-1.5"
            >
              <CheckCircle className="w-4 h-4" />
              <span>Kembalikan Berkas Fisik</span>
            </button>
          ) : !doc.status.includes('Menunggu') ? (
            <button
              onClick={() => onOpenBorrowModal(doc)}
              className="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm shadow-amber-600/30 flex items-center gap-1.5"
            >
              <Clock className="w-4 h-4" />
              <span>Pinjam Berkas Fisik</span>
            </button>
          ) : null}

          {/* Action: QR Code */}
          <button
            onClick={() => onNavigate('qrcodes', { docId: doc.id })}
            className="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm flex items-center gap-1.5"
          >
            <QrCode className="w-4 h-4 text-slate-600" />
            <span>Cetak Label QR</span>
          </button>

          {/* Action: Edit */}
          <button
            onClick={() => onNavigate('document-edit', doc.id)}
            className="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm flex items-center gap-1.5"
          >
            <Edit className="w-4 h-4 text-slate-600" />
            <span>Edit</span>
          </button>
        </div>
      </div>

      {/* Pending Physical Verification Alert Banner */}
      {doc.status.includes('Menunggu') && (
        <div className="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 mt-0.5">
              <AlertTriangle className="w-5 h-5" />
            </div>
            <div>
              <div className="text-xs font-black text-amber-950 uppercase tracking-wide">
                Berkas Fisik Belum Diverifikasi di Gudang
              </div>
              <p className="text-[11px] text-amber-800 mt-0.5 leading-relaxed">
                Dokumen ini baru tersimpan secara digital. Petugas arsip gudang perlu memverifikasi kelengkapan berkas fisik asli (hardcopy), memeriksa cap basah, dan menempatkannya ke ordner box rak gudang.
              </p>
            </div>
          </div>

          {onOpenVerificationModal && (
            <button
              onClick={() => onOpenVerificationModal(doc)}
              className="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm whitespace-nowrap flex-shrink-0 flex items-center gap-1.5 self-start sm:self-center"
            >
              <ShieldCheck className="w-4 h-4" />
              <span>Verifikasi & Tempatkan Sekarang</span>
            </button>
          )}
        </div>
      )}

      {/* Revision Required Alert Banner */}
      {doc.status.includes('Revisi') && (
        <div className="p-4 rounded-2xl bg-gradient-to-r from-rose-50 to-amber-50 border-2 border-rose-300 text-rose-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5">
              <RotateCcw className="w-5 h-5 stroke-[2.5]" />
            </div>
            <div>
              <div className="text-xs font-black text-rose-950 uppercase tracking-wide flex items-center gap-2">
                <span>BERKAS FISIK MEMERLUKAN REVISI / PERBAIKAN</span>
                <span className="px-2 py-0.5 rounded-full bg-rose-200 text-rose-900 text-[10px] font-bold">
                  Perlu Tindakan Pemohon
                </span>
              </div>
              <p className="text-xs text-rose-800 font-semibold mt-1">
                Catatan Petugas Gudang: <span className="italic text-rose-950 font-bold">"{doc.catatan_verifikasi || 'Lengkapi kekurangan tanda tangan atau lampiran fisik dokumen'}"</span>
              </p>
              <p className="text-[11px] text-rose-700 mt-0.5">
                Silakan edit formulir PB atau unggah lampiran digital pendukung yang baru, kemudian serahkan kembali hardcopy fisik ke gudang untuk diverifikasi ulang.
              </p>
            </div>
          </div>

          <button
            onClick={() => onNavigate('document-edit', doc.id)}
            className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm whitespace-nowrap flex-shrink-0 flex items-center gap-1.5 self-start sm:self-center"
          >
            <Edit className="w-4 h-4" />
            <span>Perbaiki Dokumen PB</span>
          </button>
        </div>
      )}

      {/* Official Physical Verification Seal Badge */}
      {doc.verified_at && (
        <div className="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200 text-emerald-900 flex items-center justify-between gap-3 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0">
              <FileCheck2 className="w-5 h-5" />
            </div>
            <div>
              <div className="text-xs font-black text-emerald-950 flex items-center gap-2">
                <span>BERKAS FISIK TELAH DIVERIFIKASI & DIARSIPKAN</span>
                <span className="px-2 py-0.5 rounded-full text-[10px] bg-emerald-200 text-emerald-800 font-bold">
                  {doc.kondisi_berkas_fisik || 'Asli & Cap Basah Lengkap'}
                </span>
              </div>
              <div className="text-[11px] text-emerald-800 mt-0.5">
                Diverifikasi oleh: <strong>{doc.verified_by || 'Staff Warehouse'}</strong> pada <strong>{doc.verified_at}</strong> • Lokasi Ordner: <strong>{doc.lokasi_rak} – {doc.nomor_box}</strong>
              </div>
              {doc.catatan_verifikasi && (
                <div className="text-[11px] text-emerald-700 italic mt-0.5">
                  BAP: "{doc.catatan_verifikasi}"
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Active Borrowing Banner (If currently borrowed) */}
      {isBorrowed && (
        <div className="bg-rose-50 border-2 border-rose-300 rounded-2xl p-5 shadow-sm space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                <AlertTriangle className="w-5 h-5 stroke-[2.5]" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h4 className="text-sm font-extrabold text-rose-950">DOKUMEN FISIK SEDANG DIPINJAM KELUAR</h4>
                  {doc.active_borrowing.is_overdue && (
                    <span className="px-2 py-0.5 rounded-full bg-rose-600 text-white text-[10px] font-extrabold animate-pulse">
                      TERLAMBAT {Math.abs(doc.active_borrowing.sisa_hari)} HARI
                    </span>
                  )}
                </div>
                <p className="text-xs text-rose-800 mt-0.5">
                  Peminjam: <strong>{doc.active_borrowing.nama_peminjam}</strong> ({doc.active_borrowing.departemen_peminjam})
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <a
                href={doc.active_borrowing.slip_url}
                target="_blank"
                rel="noreferrer"
                className="px-3 py-1.5 rounded-lg border border-rose-300 bg-white hover:bg-rose-50 text-rose-800 text-xs font-bold"
              >
                Cetak Slip Pinjam
              </a>
              <button
                onClick={() => onOpenReturnModal(doc.active_borrowing.id, doc.nomor_pb, doc.active_borrowing.nama_peminjam)}
                className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm"
              >
                ✓ Terima Kembali
              </button>
            </div>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-rose-200/60 text-xs text-rose-900">
            <div>
              <span className="text-rose-600 text-[11px] block">Tanggal Pinjam:</span>
              <strong>{doc.active_borrowing.tanggal_pinjam}</strong>
            </div>
            <div>
              <span className="text-rose-600 text-[11px] block">Jatuh Tempo:</span>
              <strong className={doc.active_borrowing.is_overdue ? 'text-rose-700 underline' : ''}>
                {doc.active_borrowing.batas_kembali}
              </strong>
            </div>
            <div className="col-span-2">
              <span className="text-rose-600 text-[11px] block">Keperluan:</span>
              <span className="italic">{doc.active_borrowing.keperluan}</span>
            </div>
          </div>
        </div>
      )}

      {/* Main Info Grid & QR Code Card */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Info Card (2 Cols) */}
        <div className="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-5">
          <div className="border-b border-slate-100 pb-3">
            <h3 className="text-sm font-extrabold uppercase tracking-wider text-slate-400">Data Pengarsipan</h3>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Nomor PB</span>
              <span className="font-mono font-bold text-sky-700 text-sm">{doc.nomor_pb}</span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Tanggal PB</span>
              <span className="font-semibold text-slate-900">{doc.tanggal_indonesia}</span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Departemen / Bagian</span>
              <span className="font-semibold text-slate-900">{doc.bagian}</span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Status Dokumen</span>
              <span
                className={`inline-block px-2.5 py-0.5 rounded-full font-bold text-[11px] mt-0.5 ${
                  doc.status.includes('Terverifikasi')
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                    : doc.status.includes('Revisi')
                    ? 'bg-gradient-to-r from-rose-50 to-amber-50 text-rose-800 border border-rose-300 font-extrabold shadow-sm'
                    : doc.status.includes('Dipinjam')
                    ? 'bg-rose-50 text-rose-700 border border-rose-200'
                    : 'bg-amber-50 text-amber-700 border border-amber-200'
                }`}
              >
                {doc.status}
              </span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Nama Pemohon</span>
              <span className="font-semibold text-slate-900">{doc.nama_pemohon}</span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">NIP Pemohon</span>
              <span className="font-mono text-slate-600">{doc.nip_pemohon || '–'}</span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Lokasi Fisik Gudang</span>
              <span className="inline-block px-2.5 py-1 rounded bg-sky-50 text-sky-800 border border-sky-200 font-bold text-xs mt-0.5">
                {doc.lokasi_rak} – {doc.nomor_box}
              </span>
            </div>
            <div>
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Diarsipkan Oleh</span>
              <span className="font-semibold text-slate-700">{doc.uploaded_by}</span>
            </div>
            <div className="sm:col-span-2">
              <span className="text-slate-400 font-bold uppercase text-[10px] block">Keterangan PB</span>
              <p className="text-slate-700 leading-relaxed mt-0.5">{doc.keterangan || '–'}</p>
            </div>
            {doc.catatan_arsip && (
              <div className="sm:col-span-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <span className="text-slate-500 font-bold uppercase text-[10px] block">Catatan Arsip Fisik:</span>
                <p className="text-slate-800 mt-0.5">{doc.catatan_arsip}</p>
              </div>
            )}
          </div>
        </div>

        {/* Mini QR Card (1 Col) */}
        <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col items-center justify-between text-center">
          <div className="w-full">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
              Identitas QR Code Fisik
            </h4>
            <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl inline-block shadow-inner">
              <img
                src={`https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=${encodeURIComponent(
                  window.location.origin + '/documents/' + doc.id
                )}`}
                alt="QR Code"
                className="w-32 h-32"
              />
            </div>
            <p className="text-[11px] text-slate-400 mt-2 font-medium">
              Scan stiker ini untuk membuka dokumen langsung di ponsel petugas gudang.
            </p>
          </div>

          <button
            onClick={() => onNavigate('qrcodes', { docId: doc.id })}
            className="w-full mt-4 py-2 px-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-sky-50 hover:text-sky-700 hover:border-sky-200 text-xs font-bold text-slate-700 transition-colors flex items-center justify-center gap-1.5"
          >
            <Printer className="w-3.5 h-3.5" />
            <span>Cetak Stiker Ordner Fisik</span>
          </button>
        </div>
      </div>

      {/* Multi-Attachment Management Section */}
      <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-5">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <h3 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
              <Paperclip className="w-5 h-5 text-sky-600" />
              <span>Berkas Lampiran Pendukung ({doc.attachments?.length || 0})</span>
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Scan Asli PB, Purchase Order (PO), Delivery Order (DO), BAST, dan Sertifikat Uji QC
            </p>
          </div>

          <button
            onClick={() => setShowUploadForm(!showUploadForm)}
            className="px-3.5 py-1.5 rounded-xl border border-sky-600 text-sky-700 hover:bg-sky-50 text-xs font-bold transition-colors inline-flex items-center gap-1.5"
          >
            <Upload className="w-3.5 h-3.5" />
            <span>{showUploadForm ? 'Tutup Form Upload' : '+ Upload Lampiran Baru'}</span>
          </button>
        </div>

        {/* Upload Form (Toggled) */}
        {showUploadForm && (
          <form
            onSubmit={handleUploadAttachment}
            className="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4 animate-in fade-in duration-200"
          >
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">
                  Klasifikasi Berkas <span className="text-rose-500">*</span>
                </label>
                <select
                  value={jenisDokumen}
                  onChange={(e) => setJenisDokumen(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white"
                >
                  <option value="Form PB Scan Asli">Form PB Scan Asli</option>
                  <option value="Purchase Order (PO)">Purchase Order (PO)</option>
                  <option value="Surat Jalan / Delivery Order (DO)">Surat Jalan / Delivery Order (DO)</option>
                  <option value="Berita Acara Serah Terima (BAST)">Berita Acara Serah Terima (BAST)</option>
                  <option value="Sertifikat Kalibrasi / Inspeksi QC">Sertifikat Kalibrasi / Inspeksi QC</option>
                  <option value="Dokumen Pendukung Lainnya">Dokumen Pendukung Lainnya</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">
                  Pilih Berkas (PDF, JPG, PNG maks 10MB) <span className="text-rose-500">*</span>
                </label>
                <input
                  type="file"
                  required
                  accept=".pdf,.jpg,.jpeg,.png"
                  onChange={(e) => setAttachmentFile(e.target.files[0])}
                  className="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowUploadForm(false)}
                className="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-200"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={uploading}
                className="px-4 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm"
              >
                {uploading ? 'Mengunggah...' : 'Unggah Sekarang'}
              </button>
            </div>
          </form>
        )}

        {/* Attachments Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
          {doc.attachments?.map((att) => (
            <div
              key={att.id}
              className="p-4 rounded-xl border border-slate-200 bg-white hover:border-sky-300 hover:shadow-md transition-all flex flex-col justify-between"
            >
              <div>
                <div className="flex items-center justify-between text-xs text-sky-700 font-extrabold uppercase">
                  <span>{att.jenis_dokumen}</span>
                  <button
                    onClick={() => handleDeleteAttachment(att.id, att.file_name)}
                    className="text-slate-400 hover:text-rose-600 p-0.5"
                    title="Hapus Lampiran"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
                <div className="text-xs font-bold text-slate-900 mt-2 truncate" title={att.file_name}>
                  {att.file_name}
                </div>
                <div className="text-[11px] text-slate-400 mt-0.5">Ukuran: {att.file_size || '–'}</div>
              </div>

              <div className="flex items-center gap-2 pt-3 border-t border-slate-100 mt-3">
                <button
                  onClick={() =>
                    onOpenPreview({
                      name: att.file_name,
                      type: att.jenis_dokumen,
                      url: att.preview_url,
                      downloadUrl: att.download_url,
                      isImage: att.is_image,
                    })
                  }
                  className="flex-1 py-1.5 px-2 bg-slate-50 hover:bg-sky-50 hover:text-sky-700 text-slate-700 rounded-lg text-xs font-bold text-center transition-colors flex items-center justify-center gap-1"
                >
                  <Eye className="w-3 h-3" />
                  <span>Pratinjau</span>
                </button>
                <a
                  href={att.download_url}
                  download
                  className="flex-1 py-1.5 px-2 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold text-center transition-colors flex items-center justify-center gap-1"
                >
                  <Download className="w-3 h-3" />
                  <span>Unduh</span>
                </a>
              </div>
            </div>
          ))}

          {(!doc.attachments || doc.attachments.length === 0) && (
            <div className="col-span-full text-center py-8 text-slate-400 text-xs font-medium">
              Belum ada berkas lampiran pendukung. Klik tombol di atas untuk mengunggah.
            </div>
          )}
        </div>
      </div>

      {/* Rincian Barang Permintaan Table */}
      {doc.items && doc.items.length > 0 && (
        <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
          <h3 className="text-base font-extrabold text-slate-900">
            Daftar Barang yang Diminta ({doc.items.length} Item)
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-200 text-[11px] font-extrabold uppercase text-slate-500">
                  <th className="py-2.5 px-3">No</th>
                  <th className="py-2.5 px-3">Nama Barang</th>
                  <th className="py-2.5 px-3">Part Number</th>
                  <th className="py-2.5 px-3 text-center">Jumlah</th>
                  <th className="py-2.5 px-3">Satuan</th>
                  <th className="py-2.5 px-3">Harga Satuan</th>
                  <th className="py-2.5 px-3 font-bold">Subtotal</th>
                  <th className="py-2.5 px-3">Spesifikasi Teknis</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {doc.items.map((item, idx) => (
                  <tr key={item.id} className="hover:bg-slate-50/60">
                    <td className="py-2.5 px-3 text-slate-400">{idx + 1}</td>
                    <td className="py-2.5 px-3 font-bold text-slate-900">{item.nama}</td>
                    <td className="py-2.5 px-3 font-mono text-sky-700">{item.part_number || '–'}</td>
                    <td className="py-2.5 px-3 text-center font-bold">{item.jumlah}</td>
                    <td className="py-2.5 px-3 text-slate-600">{item.satuan}</td>
                    <td className="py-2.5 px-3 text-slate-600">Rp {Number(item.harga_satuan).toLocaleString('id-ID')}</td>
                    <td className="py-2.5 px-3 font-bold text-slate-900">
                      Rp {(item.jumlah * item.harga_satuan).toLocaleString('id-ID')}
                    </td>
                    <td className="py-2.5 px-3 text-slate-500 italic">{item.spesifikasi || '–'}</td>
                  </tr>
                ))}
                <tr className="bg-slate-50 font-extrabold text-slate-900">
                  <td colSpan={6} className="py-3 px-3 text-right">Total Anggaran Permintaan:</td>
                  <td className="py-3 px-3 text-sky-700">Rp {Number(doc.total_harga).toLocaleString('id-ID')}</td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Riwayat Peminjaman Table */}
      {doc.borrowing_history && doc.borrowing_history.length > 0 && (
        <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
          <h3 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
            <Clock className="w-5 h-5 text-sky-600" />
            <span>Riwayat Sirkulasi Peminjaman Berkas ({doc.borrowing_history.length})</span>
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-200 text-[11px] font-extrabold uppercase text-slate-500">
                  <th className="py-2.5 px-3">Peminjam</th>
                  <th className="py-2.5 px-3">Departemen</th>
                  <th className="py-2.5 px-3">Keperluan</th>
                  <th className="py-2.5 px-3">Tgl Pinjam</th>
                  <th className="py-2.5 px-3">Batas Kembali</th>
                  <th className="py-2.5 px-3">Tgl Kembali</th>
                  <th className="py-2.5 px-3">Status</th>
                  <th className="py-2.5 px-3 text-right">Slip</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {doc.borrowing_history.map((b) => (
                  <tr key={b.id} className="hover:bg-slate-50/60">
                    <td className="py-2.5 px-3 font-bold text-slate-900">{b.nama_peminjam}</td>
                    <td className="py-2.5 px-3 text-slate-600">{b.departemen_peminjam}</td>
                    <td className="py-2.5 px-3 text-slate-700 italic max-w-xs truncate">{b.keperluan}</td>
                    <td className="py-2.5 px-3 text-slate-700">{b.tanggal_pinjam}</td>
                    <td className="py-2.5 px-3 text-slate-700 font-semibold">{b.batas_kembali}</td>
                    <td className="py-2.5 px-3 text-slate-700">{b.tanggal_kembali || '–'}</td>
                    <td className="py-2.5 px-3">
                      <span
                        className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                          b.status_pinjam === 'Dikembalikan'
                            ? 'bg-emerald-50 text-emerald-700'
                            : b.is_overdue
                            ? 'bg-rose-100 text-rose-700'
                            : 'bg-amber-100 text-amber-700'
                        }`}
                      >
                        {b.status_pinjam}
                      </span>
                    </td>
                    <td className="py-2.5 px-3 text-right">
                      <a
                        href={b.slip_url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-sky-600 hover:underline font-bold text-[11px]"
                      >
                        Slip
                      </a>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
