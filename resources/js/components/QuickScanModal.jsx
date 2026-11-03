import React, { useState, useEffect, useRef } from 'react';
import {
  Camera,
  Barcode,
  Search,
  X,
  CheckCircle2,
  AlertTriangle,
  FolderOpen,
  Box as BoxIcon,
  ExternalLink,
  ShieldCheck,
  RotateCcw,
  Sparkles,
  Zap,
} from 'lucide-react';
import { Html5Qrcode } from 'html5-qrcode';
import { scannerApi } from '../api';

export default function QuickScanModal({
  user,
  isOpen,
  onClose,
  onToast,
  onOpenVerification,
  onOpenBorrow,
  onOpenReturn,
  onOpenBox,
  onNavigateDetail,
}) {
  if (!isOpen) return null;

  const canManage = user?.can_manage_warehouse;

  const [activeTab, setActiveTab] = useState('camera'); // 'camera' or 'gun'
  const [manualCode, setManualCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [scanResult, setScanResult] = useState(null);
  const [cameraError, setCameraError] = useState(null);
  const [cameras, setCameras] = useState([]);
  const [selectedCameraId, setSelectedCameraId] = useState('');
  const [isScanning, setIsScanning] = useState(false);

  const html5QrCodeRef = useRef(null);
  const gunInputRef = useRef(null);

  // Play synthetic warehouse scanner beep sound using Web Audio API
  const playBeep = () => {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime); // 880Hz (A5) clear high beep
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);

      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.12);
    } catch (e) {
      // Audio not permitted without interaction, ignore
    }
  };

  // Keyboard shortcut Esc to close
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        handleClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

  // Autofocus input in Gun mode
  useEffect(() => {
    if (activeTab === 'gun' && gunInputRef.current) {
      gunInputRef.current.focus();
    }
  }, [activeTab, scanResult]);

  // Reactive Auto-Refresh & Auto-Scan when inputting via Barcode Gun or Manual Typing
  useEffect(() => {
    if (activeTab !== 'gun' || !manualCode.trim() || loading || scanResult) return;

    const trimmed = manualCode.trim();

    // Instant trigger if it matches full barcode format (e.g. PB/BMS-WH/2026/09/001 or Box 01)
    const isFullPbPattern = /^PB\/[A-Z0-9-]+\/\d{4}\/\d{2}\/\d{3}$/i.test(trimmed);
    const isFullBoxPattern = /^(Box\s+\d+|Rak\s+[A-Z]\s*-\s*Box\s+\d+)$/i.test(trimmed);

    const delay = isFullPbPattern || isFullBoxPattern ? 80 : 400;

    const timer = setTimeout(() => {
      if (trimmed.length >= 3) {
        handleCodeScanned(trimmed);
        setManualCode('');
      }
    }, delay);

    return () => clearTimeout(timer);
  }, [manualCode, activeTab, loading, scanResult]);

  // Camera initialization
  useEffect(() => {
    let isMounted = true;

    if (activeTab === 'camera' && !scanResult) {
      setCameraError(null);

      Html5Qrcode.getCameras()
        .then((devs) => {
          if (!isMounted) return;
          if (devs && devs.length > 0) {
            setCameras(devs);
            // Default to back camera (environment) if available
            const backCam = devs.find((d) => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear'));
            const camId = backCam ? backCam.id : devs[0].id;
            setSelectedCameraId(camId);
            startScanner(camId);
          } else {
            setCameraError('Kamera tidak terdeteksi pada perangkat ini. Silakan gunakan tab Barcode Gun / Input Manual.');
          }
        })
        .catch((err) => {
          if (!isMounted) return;
          console.error(err);
          setCameraError('Izin akses kamera ditolak atau kamera sedang digunakan aplikasi lain.');
        });
    }

    return () => {
      isMounted = false;
      stopScanner();
    };
  }, [activeTab, scanResult]);

  const startScanner = (cameraId) => {
    stopScanner().then(() => {
      const qrCodeRegionId = 'interactive-qr-reader';
      const element = document.getElementById(qrCodeRegionId);
      if (!element) return;

      const html5QrCode = new Html5Qrcode(qrCodeRegionId);
      html5QrCodeRef.current = html5QrCode;

      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
      };

      html5QrCode
        .start(
          cameraId,
          config,
          (decodedText) => {
            // Success scan
            handleCodeScanned(decodedText);
          },
          (errorMessage) => {
            // Frame scan failure, ignore frame errors
          }
        )
        .then(() => {
          setIsScanning(true);
        })
        .catch((err) => {
          console.error(err);
          setCameraError('Gagal memulai stream kamera. Gunakan Tab Barcode Gun.');
        });
    });
  };

  const stopScanner = () => {
    if (html5QrCodeRef.current) {
      return html5QrCodeRef.current
        .stop()
        .then(() => {
          html5QrCodeRef.current?.clear();
          html5QrCodeRef.current = null;
          setIsScanning(false);
        })
        .catch(() => {
          html5QrCodeRef.current = null;
          setIsScanning(false);
        });
    }
    return Promise.resolve();
  };

  const handleClose = () => {
    stopScanner();
    onClose();
  };

  const handleCodeScanned = async (code) => {
    if (!code || loading) return;
    stopScanner();
    playBeep();
    setLoading(true);

    try {
      const res = await scannerApi.resolve(code);
      setScanResult(res.data);
      onToast(res.data.message || 'Kode berhasil dipindai.', 'success');
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.message || `Kode '${code}' tidak ditemukan dalam database.`;
      onToast(msg, 'error');
      // If camera tab, restart after small delay
      if (activeTab === 'camera') {
        setTimeout(() => {
          if (selectedCameraId) startScanner(selectedCameraId);
        }, 1200);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleGunSubmit = (e) => {
    e.preventDefault();
    if (manualCode.trim()) {
      handleCodeScanned(manualCode.trim());
      setManualCode('');
    }
  };

  const handleResetScan = () => {
    setScanResult(null);
    setManualCode('');
    if (activeTab === 'camera' && selectedCameraId) {
      setTimeout(() => startScanner(selectedCameraId), 200);
    } else if (activeTab === 'gun') {
      setTimeout(() => gunInputRef.current?.focus(), 200);
    }
  };

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5">
      <div className="bg-white rounded-3xl shadow-2xl w-full max-w-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 overflow-hidden flex flex-col max-h-[92vh]">
        {/* Top Header */}
        <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center">
              <Camera className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-mono font-bold tracking-wider px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-300">
                  SMART SCANNER
                </span>
                <span className="text-[11px] text-slate-400 font-mono">BMS WAREHOUSE v2.0</span>
              </div>
              <h3 className="text-base font-black text-white tracking-tight mt-0.5">
                Pemindai Barcode / QR Code Fisik
              </h3>
            </div>
          </div>

          <button
            onClick={handleClose}
            className="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
            title="Tutup (Esc)"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Tab Switcher (Kamera vs Barcode Gun) */}
        {!scanResult && (
          <div className="bg-slate-100 p-2 border-b border-slate-200 flex items-center gap-2">
            <button
              onClick={() => setActiveTab('camera')}
              className={`flex-1 py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all ${
                activeTab === 'camera'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Camera className="w-4 h-4 text-sky-600" />
              <span>Kamera Video Stream</span>
            </button>

            <button
              onClick={() => {
                stopScanner();
                setActiveTab('gun');
              }}
              className={`flex-1 py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all ${
                activeTab === 'gun'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Barcode className="w-4 h-4 text-sky-600" />
              <span>Barcode Gun / Manual</span>
            </button>
          </div>
        )}

        {/* Modal Body */}
        <div className="p-5 overflow-y-auto space-y-4 flex-1">
          {/* STATE 1: SCAN RESULT FOUND (Action Dispatcher) */}
          {scanResult ? (
            <div className="space-y-4 animate-in fade-in zoom-in-95 duration-150">
              {/* Type Badge & Identity Card */}
              <div className="p-4 rounded-2xl bg-slate-900 text-white border border-slate-800 space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse" />
                    <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-sky-400">
                      HASIL PEMINDAIAN: {scanResult.type === 'document' ? 'DOKUMEN PB' : 'ORDNER BOX'}
                    </span>
                  </div>
                  <span className="text-[11px] font-mono text-slate-400">Kode: {scanResult.code}</span>
                </div>

                <div className="border-t border-slate-800 pt-2.5 flex items-baseline justify-between">
                  <div>
                    <h4 className="text-xl font-mono font-black text-white tracking-tight">
                      {scanResult.type === 'document'
                        ? scanResult.data.nomor_pb
                        : `${scanResult.data.rack_kode} • ${scanResult.data.nomor_box}`}
                    </h4>
                    <p className="text-xs text-slate-300 mt-0.5">
                      {scanResult.type === 'document'
                        ? `${scanResult.data.bagian} • Pemohon: ${scanResult.data.nama_pemohon}`
                        : `${scanResult.data.rack_nama} (${scanResult.data.zona_lokasi})`}
                    </p>
                  </div>

                  <span
                    className={`text-xs font-bold px-2.5 py-1 rounded-full ${
                      scanResult.type === 'document'
                        ? scanResult.data.status.includes('Terverifikasi')
                          ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                          : scanResult.data.status.includes('Dipinjam')
                          ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
                          : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                        : 'bg-sky-500/20 text-sky-300 border border-sky-500/30'
                    }`}
                  >
                    {scanResult.type === 'document' ? scanResult.data.status : scanResult.data.status}
                  </span>
                </div>

                {/* Additional Metadata Pill Grid */}
                <div className="grid grid-cols-2 gap-2 pt-1 text-xs font-mono text-slate-300">
                  {scanResult.type === 'document' ? (
                    <>
                      <div className="p-2 rounded-xl bg-slate-800/80 border border-slate-700/60">
                        <span className="text-[10px] text-slate-400 block">Lokasi Rak & Box:</span>
                        <strong className="text-white">
                          {scanResult.data.lokasi_rak} - {scanResult.data.nomor_box}
                        </strong>
                      </div>
                      <div className="p-2 rounded-xl bg-slate-800/80 border border-slate-700/60">
                        <span className="text-[10px] text-slate-400 block">Tanggal Dokumen:</span>
                        <strong className="text-white">{scanResult.data.tanggal_indonesia}</strong>
                      </div>
                    </>
                  ) : (
                    <>
                      <div className="p-2 rounded-xl bg-slate-800/80 border border-slate-700/60">
                        <span className="text-[10px] text-slate-400 block">Keterisian Box:</span>
                        <strong className="text-white">
                          {scanResult.data.total_dokumen} / {scanResult.data.kapasitas_maksimal} Map (
                          {scanResult.data.persentase_kapasitas}%)
                        </strong>
                      </div>
                      <div className="p-2 rounded-xl bg-slate-800/80 border border-slate-700/60">
                        <span className="text-[10px] text-slate-400 block">Posisi Ambalan:</span>
                        <strong className="text-white">
                          Baris {scanResult.data.baris_ke} • Kolom {scanResult.data.kolom_ke}
                        </strong>
                      </div>
                    </>
                  )}
                </div>

                {/* Active Borrowing Alert if Document is Borrowed */}
                {scanResult.type === 'document' && scanResult.data.active_borrowing && (
                  <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                    <div className="font-bold flex items-center gap-1.5 text-rose-400">
                      <AlertTriangle className="w-4 h-4" />
                      <span>Sedang Dipinjam: {scanResult.data.active_borrowing.nama_peminjam}</span>
                    </div>
                    <div className="text-[11px] text-slate-300">
                      Batas Kembali: {scanResult.data.active_borrowing.batas_kembali}{' '}
                      {scanResult.data.active_borrowing.is_overdue && (
                        <span className="text-rose-400 font-bold ml-1">(TERLAMBAT!)</span>
                      )}
                    </div>
                  </div>
                )}
              </div>

              {/* Recommended Action CTA Buttons */}
              <div className="space-y-2 pt-2">
                <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                  Aksi yang Direkomendasikan:
                </span>

                {/* Case 1: Document in Pending Physical Verification */}
                {scanResult.type === 'document' && scanResult.action === 'verify_physical' && (
                  canManage ? (
                    <button
                      onClick={() => {
                        handleClose();
                        onOpenVerification(scanResult.data);
                      }}
                      className="w-full py-3 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-lg shadow-sky-600/30 flex items-center justify-center gap-2 transition-all"
                    >
                      <ShieldCheck className="w-4 h-4" />
                      <span>✓ Verifikasi Fisik & Sahkan ke Rak Gudang</span>
                    </button>
                  ) : (
                    <div className="space-y-2">
                      <button
                        disabled
                        className="w-full py-2.5 rounded-xl bg-slate-800 text-slate-500 text-xs font-bold cursor-not-allowed flex items-center justify-center gap-2 border border-slate-700"
                        title="Hanya Admin/Petugas Warehouse yang berhak memverifikasi fisik dokumen"
                      >
                        <ShieldCheck className="w-4 h-4 text-slate-500" />
                        <span>Verifikasi Fisik (Khusus Admin WH)</span>
                      </button>
                      <button
                        onClick={() => {
                          handleClose();
                          onNavigateDetail(scanResult.data.id);
                        }}
                        className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all border border-slate-700"
                      >
                        <ExternalLink className="w-4 h-4" />
                        <span>Buka Lembar Detail Dokumen PB</span>
                      </button>
                    </div>
                  )
                )}

                {/* Case 2: Document is Archived & Ready to Borrow */}
                {scanResult.type === 'document' && scanResult.action === 'borrow_document' && (
                  canManage ? (
                    <div className="grid grid-cols-2 gap-2">
                      <button
                        onClick={() => {
                          handleClose();
                          onOpenBorrow(scanResult.data);
                        }}
                        className="py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all"
                      >
                        <FolderOpen className="w-4 h-4" />
                        <span>1-Click Pinjamkan</span>
                      </button>

                      <button
                        onClick={() => {
                          handleClose();
                          onNavigateDetail(scanResult.data.id);
                        }}
                        className="py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all"
                      >
                        <ExternalLink className="w-4 h-4" />
                        <span>Buka Detail PB</span>
                      </button>
                    </div>
                  ) : (
                    <button
                      onClick={() => {
                        handleClose();
                        onNavigateDetail(scanResult.data.id);
                      }}
                      className="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all"
                    >
                      <ExternalLink className="w-4 h-4" />
                      <span>Buka Lembar Detail Dokumen PB</span>
                    </button>
                  )
                )}

                {/* Case 3: Document is currently borrowed -> Return physical */}
                {scanResult.type === 'document' && scanResult.action === 'return_physical' && (
                  canManage ? (
                    <button
                      onClick={() => {
                        handleClose();
                        onOpenReturn(scanResult.data.active_borrowing);
                      }}
                      className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all"
                    >
                      <CheckCircle2 className="w-4 h-4" />
                      <span>✓ Proses Pengembalian Berkas Fisik</span>
                    </button>
                  ) : (
                    <div className="space-y-2">
                      <button
                        disabled
                        className="w-full py-2.5 rounded-xl bg-slate-800 text-slate-500 text-xs font-bold cursor-not-allowed flex items-center justify-center gap-2 border border-slate-700"
                        title="Pengesahan pengembalian hanya dapat diproses oleh Admin/Petugas Warehouse"
                      >
                        <CheckCircle2 className="w-4 h-4 text-slate-500" />
                        <span>Pengembalian Fisik (Khusus Admin WH)</span>
                      </button>
                      <button
                        onClick={() => {
                          handleClose();
                          onNavigateDetail(scanResult.data.id);
                        }}
                        className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all border border-slate-700"
                      >
                        <ExternalLink className="w-4 h-4" />
                        <span>Buka Lembar Detail Dokumen PB</span>
                      </button>
                    </div>
                  )
                )}

                {/* Case 4: Box Scanned -> Open Box Dossier */}
                {scanResult.type === 'box' && (
                  <button
                    onClick={() => {
                      handleClose();
                      onOpenBox(scanResult.data);
                    }}
                    className="w-full py-3 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-lg shadow-sky-600/30 flex items-center justify-center gap-2 transition-all"
                  >
                    <BoxIcon className="w-4 h-4" />
                    <span>Buka Ordner Dossier (Denah 2D)</span>
                  </button>
                )}

                {/* Default: View Detail */}
                {scanResult.type === 'document' && scanResult.action === 'view_detail' && (
                  <button
                    onClick={() => {
                      handleClose();
                      onNavigateDetail(scanResult.data.id);
                    }}
                    className="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all"
                  >
                    <ExternalLink className="w-4 h-4" />
                    <span>Buka Lembar Detail Dokumen PB</span>
                  </button>
                )}
              </div>

              {/* Reset Scan Button */}
              <div className="pt-2 text-center">
                <button
                  onClick={handleResetScan}
                  className="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors inline-flex items-center gap-1.5"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Pindai Barcode Lain</span>
                </button>
              </div>
            </div>
          ) : activeTab === 'camera' ? (
            /* STATE 2A: CAMERA VIEW STREAM */
            <div className="space-y-3">
              {cameraError ? (
                <div className="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-center space-y-3">
                  <AlertTriangle className="w-8 h-8 mx-auto text-amber-600" />
                  <p className="text-xs font-semibold">{cameraError}</p>
                  <button
                    onClick={() => setActiveTab('gun')}
                    className="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-sm"
                  >
                    Beralih ke Input Barcode Gun
                  </button>
                </div>
              ) : (
                <div className="space-y-3">
                  {/* Camera Video Stream Container with Laser Line Animation */}
                  <div className="relative rounded-2xl overflow-hidden bg-slate-950 aspect-square max-h-[300px] mx-auto border-2 border-slate-800 shadow-inner flex items-center justify-center">
                    <div id="interactive-qr-reader" className="w-full h-full" />

                    {/* Animated Red Laser Scan Overlay */}
                    {isScanning && (
                      <div className="absolute inset-x-4 top-1/2 -translate-y-1/2 pointer-events-none">
                        <div className="h-0.5 w-full bg-rose-500 shadow-lg shadow-rose-500/80 animate-pulse" />
                      </div>
                    )}
                  </div>

                  <p className="text-[11px] text-center text-slate-500">
                    Arahkan kamera ke stiker thermal QR Code atau Barcode pada ordner box / lembar PB.
                  </p>

                  {/* Switch Camera if multiple cameras exist */}
                  {cameras.length > 1 && (
                    <div className="flex items-center justify-center gap-2">
                      <span className="text-[11px] text-slate-500 font-medium">Pilih Kamera:</span>
                      <select
                        value={selectedCameraId}
                        onChange={(e) => {
                          setSelectedCameraId(e.target.value);
                          startScanner(e.target.value);
                        }}
                        className="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-medium bg-white"
                      >
                        {cameras.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.label || `Kamera ${c.id.substring(0, 5)}`}
                          </option>
                        ))}
                      </select>
                    </div>
                  )}
                </div>
              )}
            </div>
          ) : (
            /* STATE 2B: BARCODE GUN / MANUAL INPUT */
            <div className="space-y-4 py-2">
              <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-2">
                <Barcode className="w-10 h-10 mx-auto text-sky-600" />
                <h4 className="text-xs font-bold text-slate-800">
                  Mode Barcode Gun (USB / Bluetooth) & Input Manual
                </h4>
                <p className="text-[11px] text-slate-500 max-w-sm mx-auto leading-relaxed">
                  Tembakkan barcode gun ke stiker fisik. Pemindai otomatis membaca string barcode dan menekan Enter.
                </p>
              </div>

              <form onSubmit={handleGunSubmit} className="space-y-3">
                <div className="relative">
                  <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                  <input
                    ref={gunInputRef}
                    type="text"
                    value={manualCode}
                    onChange={(e) => setManualCode(e.target.value)}
                    placeholder="Scan atau ketik: PB/BMS-WH/2026/09/001 atau Box 01..."
                    className="w-full pl-10 pr-10 py-3 rounded-2xl border-2 border-sky-500 text-xs font-mono font-bold focus:ring-4 focus:ring-sky-500/20 focus:outline-none bg-white shadow-sm"
                  />
                  {manualCode && (
                    <button
                      type="button"
                      onClick={() => setManualCode('')}
                      className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  )}
                </div>

                <div className="flex items-center justify-between text-[11px] text-slate-500 px-1">
                  <span className="inline-flex items-center gap-1.5 font-bold text-emerald-600">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
                    Auto-Refresh Aktif: Langsung scan tanpa perlu klik tombol
                  </span>
                </div>

                <button
                  type="submit"
                  disabled={loading || !manualCode.trim()}
                  className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center justify-center gap-2 transition-all disabled:opacity-50"
                >
                  <Zap className="w-4 h-4 text-sky-400" />
                  <span>{loading ? 'Memproses Kode...' : 'Cari Data Fisik (Enter)'}</span>
                </button>
              </form>
            </div>
          )}
        </div>

        {/* Modal Bottom Footer */}
        <div className="px-5 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
          <span>
            Shortcut: <kbd className="px-1.5 py-0.5 rounded bg-white border font-mono font-bold">Esc</kbd> untuk menutup
          </span>
          <button
            onClick={handleClose}
            className="px-4 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-white"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  );
}
