import React, { useState, useEffect } from 'react';
import { QrCode, Printer, CheckSquare, Layers, FileText } from 'lucide-react';
import { qrCodeApi } from '../api';

export default function QrCodePrintPage({ onNavigate, onToast, initialType = 'boxes' }) {
  const [type, setType] = useState(initialType);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchLabels = async () => {
    try {
      setLoading(true);
      const res = await qrCodeApi.getBatch(type);
      setItems(res.data.items || []);
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.message || 'Gagal memuat label QR Code. Pastikan database telah di-seed.';
      onToast(msg, 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLabels();
  }, [type]);

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="space-y-6">
      {/* Header & Print Action */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
        <div>
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight">
            Generator & Cetak Stiker Label QR Code Fisik
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            Format label telah disesuaikan dengan standar printer thermal (100 x 50 mm / 70 x 40 mm) maupun kertas stiker A4.
          </p>
        </div>

        <button
          onClick={handlePrint}
          className="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-lg flex items-center gap-2"
        >
          <Printer className="w-4 h-4" />
          <span>🖨️ Cetak Semua Label (Print)</span>
        </button>
      </div>

      {/* Tabs (Hide on print) */}
      <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between no-print">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setType('boxes')}
            className={`px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all ${
              type === 'boxes'
                ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`}
          >
            <Layers className="w-3.5 h-3.5" />
            <span>Label Ordner Box Fisik ({type === 'boxes' ? items.length : '...'})</span>
          </button>

          <button
            onClick={() => setType('documents')}
            className={`px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all ${
              type === 'documents'
                ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`}
          >
            <FileText className="w-3.5 h-3.5" />
            <span>Label Dokumen PB ({type === 'documents' ? items.length : '...'})</span>
          </button>
        </div>

        <span className="text-xs text-slate-400 font-medium hidden sm:inline">
          Siap cetak {items.length} label stiker
        </span>
      </div>

      {/* Labels Grid */}
      {loading ? (
        <div className="py-16 text-center text-slate-400">
          <div className="w-8 h-8 border-3 border-sky-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
          <span className="text-xs">Menyiapkan kode QR SVG vektor...</span>
        </div>
      ) : items.length === 0 ? (
        <div className="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
            <QrCode className="w-6 h-6 text-slate-400" />
          </div>
          <div>
            <h4 className="text-sm font-bold text-slate-800">
              {type === 'boxes' ? 'Belum Ada Data Ordner Box' : 'Belum Ada Data Dokumen PB'}
            </h4>
            <p className="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
              Database belum memiliki data {type === 'boxes' ? 'rak/box' : 'dokumen'}.
              Jalankan perintah <code className="bg-slate-100 px-1 py-0.5 rounded font-mono text-[11px]">php artisan db:seed</code> di terminal untuk mengisi data master.
            </p>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 print:block">
          {items.map((item, idx) => (
            <div
              key={idx}
              className="bg-white border-2 border-slate-900 rounded-xl p-4 shadow-sm flex flex-col justify-between print:mb-6 print:border-black print:page-break-inside-avoid print:w-[100mm] print:mx-auto"
              style={{ minHeight: '160px' }}
            >
              {/* Sticker Header */}
              <div className="flex items-center justify-between border-b-2 border-black pb-1.5 mb-2.5">
                <span className="text-[10px] font-black tracking-wider uppercase text-black">
                  PT BESMINDO MATERI SEWATAMA
                </span>
                <span className="text-[9px] font-extrabold bg-black text-white px-2 py-0.5 rounded">
                  {type === 'boxes' ? 'ORDNER BOX' : 'DOKUMEN PB'}
                </span>
              </div>

              {/* Body */}
              <div className="grid grid-cols-12 gap-3 items-center">
                <div className="col-span-8 space-y-1">
                  <div className="font-mono font-extrabold text-sm text-black leading-tight">
                    {item.title}
                  </div>
                  <div className="text-[11px] font-bold text-slate-800 truncate">
                    {item.subtitle}
                  </div>
                  <div className="text-[10px] text-slate-600 truncate">{item.detail_1}</div>
                  <div className="text-[10px] text-slate-600 truncate">{item.detail_2}</div>
                  <div className="inline-block text-[10px] font-extrabold border border-black px-1.5 py-0.5 rounded mt-1 text-black">
                    {item.detail_3}
                  </div>
                </div>

                <div className="col-span-4 flex flex-col items-center justify-center p-1 bg-white border border-slate-200 rounded">
                  <div
                    className="w-20 h-20 flex items-center justify-center"
                    dangerouslySetInnerHTML={{ __html: item.qr_svg }}
                  />
                  <span className="text-[8px] font-mono font-bold text-slate-600 mt-1 uppercase">
                    SCAN TO VIEW
                  </span>
                </div>
              </div>

              {/* Footer */}
              <div className="flex items-center justify-between border-t border-slate-300 pt-1.5 mt-2.5 text-[9px] text-slate-500 font-mono">
                <span>SIP-PB WH v2.0</span>
                <span>{new Date().toLocaleDateString('id-ID')}</span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
