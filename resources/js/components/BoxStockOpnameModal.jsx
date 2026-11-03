import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  Barcode,
  Camera,
  CheckCircle2,
  AlertTriangle,
  HelpCircle,
  X,
  Volume2,
  VolumeX,
  RefreshCw,
  ArrowRightLeft,
  ShieldCheck,
  Check,
  FileText,
  Search,
  Sparkles,
  Layers,
  Box as BoxIcon,
  Clock,
  User,
  Zap,
} from 'lucide-react';
import { Html5Qrcode } from 'html5-qrcode';
import { scannerApi, documentApi, rackApi } from '../api';

export default function BoxStockOpnameModal({
  isOpen,
  box,
  rack,
  initialDocuments = [],
  onClose,
  onToast,
  onSuccess,
}) {
  if (!isOpen || !box) return null;

  // Active Scan Mode: 'gun' (Barcode Gun USB / Wedge) or 'camera' (WebRTC)
  const [scanMode, setScanMode] = useState('gun');
  const [audioEnabled, setAudioEnabled] = useState(true);
  const [gunInput, setGunInput] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [auditNote, setAuditNote] = useState('');

  // Active Tab for manifest list on smaller screens: 'all', 'matched', 'missing', 'misplaced'
  const [activeTab, setActiveTab] = useState('all');

  // Camera states
  const [cameras, setCameras] = useState([]);
  const [selectedCameraId, setSelectedCameraId] = useState('');
  const [isCameraScanning, setIsCameraScanning] = useState(false);
  const [cameraError, setCameraError] = useState(null);

  // Reconciliation Map:
  // scannedItems: { [nomor_pb]: { doc, scannedAt, isForeign, originalLocation } }
  const [scannedMap, setScannedMap] = useState({});

  // Foreign / Misplaced documents scanned in this box:
  // Array of { id, nomor_pb, nama_pemohon, bagian, lokasi_rak, nomor_box, scannedAt }
  const [misplacedDocs, setMisplacedDocs] = useState([]);

  // Expected documents in this box from database
  const [expectedDocs, setExpectedDocs] = useState(initialDocuments || []);

  const inputRef = useRef(null);
  const html5QrCodeRef = useRef(null);

  // Play synthetic warehouse scanner beep sound using Web Audio API
  const playSound = (type = 'success') => {
    if (!audioEnabled) return;
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();

      if (type === 'success') {
        // High clear 880Hz beep (A5)
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        gain.gain.setValueAtTime(0.18, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
      } else if (type === 'warning') {
        // Two-tone warning beep (600Hz -> 440Hz)
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(600, ctx.currentTime);
        osc.frequency.setValueAtTime(440, ctx.currentTime + 0.08);
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.22);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.22);
      } else if (type === 'duplicate') {
        // Double soft click
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(520, ctx.currentTime);
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.06);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.06);
      }
    } catch (e) {
      // Audio not permitted, ignore
    }
  };

  // Keyboard shortcut: Escape to close, always auto-focus input in Gun mode
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        handleClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

  // Autofocus input whenever in gun mode
  useEffect(() => {
    if (scanMode === 'gun' && inputRef.current) {
      inputRef.current.focus();
    }
  }, [scanMode, scannedMap, misplacedDocs]);

  // Keep fresh documents from prop
  useEffect(() => {
    if (initialDocuments && initialDocuments.length > 0) {
      setExpectedDocs(initialDocuments);
    }
  }, [initialDocuments]);

  // Initialize camera scanner if camera mode selected
  useEffect(() => {
    if (scanMode === 'camera') {
      Html5Qrcode.getCameras()
        .then((devices) => {
          if (devices && devices.length > 0) {
            setCameras(devices);
            const backCam = devices.find((d) => d.label.toLowerCase().includes('back')) || devices[0];
            setSelectedCameraId(backCam.id);
            startCameraScanning(backCam.id);
          } else {
            setCameraError('Tidak ditemukan perangkat kamera yang terhubung.');
          }
        })
        .catch((err) => {
          console.error(err);
          setCameraError('Gagal mengakses kamera. Pastikan izin kamera telah diberikan.');
        });
    } else {
      stopCameraScanning();
    }

    return () => {
      stopCameraScanning();
    };
  }, [scanMode]);

  const startCameraScanning = (cameraId) => {
    stopCameraScanning();
    setCameraError(null);

    const html5QrCode = new Html5Qrcode('stock-opname-reader');
    html5QrCodeRef.current = html5QrCode;

    html5QrCode
      .start(
        cameraId,
        {
          fps: 15,
          qrbox: { width: 260, height: 260 },
          aspectRatio: 1.0,
        },
        (decodedText) => {
          processScannedBarcode(decodedText);
        },
        () => {
          // Frame error, ignore
        }
      )
      .then(() => {
        setIsCameraScanning(true);
      })
      .catch((err) => {
        console.error(err);
        setCameraError('Gagal memulai stream video kamera.');
        setIsCameraScanning(false);
      });
  };

  const stopCameraScanning = () => {
    if (html5QrCodeRef.current && isCameraScanning) {
      html5QrCodeRef.current
        .stop()
        .then(() => {
          html5QrCodeRef.current.clear();
          setIsCameraScanning(false);
        })
        .catch((err) => console.error(err));
    }
  };

  /**
   * Inti Logika Rekonsiliasi Real-Time (Core Matcher):
   * 1. Periksa apakah barcode cocok dengan salah satu dokumen yang tercatat di Box ini.
   * 2. Jika cocok -> tandai sebagai "Cocok / Sesuai (Hijau)" & bunyikan beep 880Hz.
   * 3. Jika tidak ada di Box ini -> panggil scannerApi.resolve untuk cek apakah dokumen nyasar dari box lain.
   */
  const processScannedBarcode = async (rawCode) => {
    const trimmed = (rawCode || '').trim();
    if (!trimmed || isProcessing) return;

    // Bersihkan format URL jika scan QR Code (misal: http://.../documents/15)
    let searchPb = trimmed;
    if (trimmed.includes('/documents/')) {
      const parts = trimmed.split('/documents/');
      const possibleId = parts[parts.length - 1];
      const matchById = expectedDocs.find((d) => String(d.id) === String(possibleId));
      if (matchById) {
        searchPb = matchById.nomor_pb;
      }
    }

    // 1. Cek apakah dokumen ini merupakan dokumen yang tercatat di Box ini (Lokal Match)
    const localMatch = expectedDocs.find(
      (d) =>
        d.nomor_pb.toLowerCase() === searchPb.toLowerCase() ||
        searchPb.toLowerCase().includes(d.nomor_pb.toLowerCase())
    );

    if (localMatch) {
      if (scannedMap[localMatch.nomor_pb]) {
        // Sudah pernah di-scan sebelumnya
        playSound('duplicate');
        onToast(`Dokumen ${localMatch.nomor_pb} sudah tercatat dalam audit ini.`, 'info');
      } else {
        // Match baru!
        playSound('success');
        setScannedMap((prev) => ({
          ...prev,
          [localMatch.nomor_pb]: {
            ...localMatch,
            scannedAt: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
          },
        }));
        onToast(`✓ Cocok: ${localMatch.nomor_pb}`, 'success');
      }
      setGunInput('');
      if (inputRef.current) inputRef.current.focus();
      return;
    }

    // 2. Jika bukan berkas lokal, cek apakah sudah masuk ke daftar 'Salah Box / Nyasar'
    const alreadyMisplaced = misplacedDocs.find(
      (m) => m.nomor_pb.toLowerCase() === searchPb.toLowerCase()
    );
    if (alreadyMisplaced) {
      playSound('duplicate');
      onToast(`Dokumen nyasar ${searchPb} sudah tercatat.`, 'info');
      setGunInput('');
      return;
    }

    // 3. Panggil backend resolve untuk mengidentifikasi berkas asing / nyasar
    try {
      setIsProcessing(true);
      const res = await scannerApi.resolve(trimmed);

      if (res.data.success && res.data.type === 'document') {
        const foundDoc = res.data.data;
        playSound('warning');

        setMisplacedDocs((prev) => [
          {
            id: foundDoc.id,
            nomor_pb: foundDoc.nomor_pb,
            nama_pemohon: foundDoc.nama_pemohon,
            bagian: foundDoc.bagian,
            lokasi_rak: foundDoc.lokasi_rak,
            nomor_box: foundDoc.nomor_box,
            status: foundDoc.status,
            scannedAt: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
          },
          ...prev,
        ]);

        onToast(`⚠️ SALAH BOX: Berkas ${foundDoc.nomor_pb} seharusnya di ${foundDoc.lokasi_rak} - ${foundDoc.nomor_box}!`, 'warning');
      } else {
        playSound('warning');
        onToast(`Barcode '${trimmed}' terdeteksi sebagai ${res.data.type || 'tipe bukan berkas'}.`, 'info');
      }
    } catch (err) {
      playSound('warning');
      console.warn('Barcode not found:', trimmed);
      onToast(`Barcode '${trimmed}' tidak ditemukan di database sistem.`, 'error');
    } finally {
      setIsProcessing(false);
      setGunInput('');
      if (inputRef.current) inputRef.current.focus();
    }
  };

  // Handle enter key or barcode gun wedge enter
  const handleGunKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (gunInput.trim()) {
        processScannedBarcode(gunInput);
      }
    }
  };

  // 1-Click Action: Relokasi Dokumen Nyasar ke Box ini Seketika
  const handleRelocateToThisBox = async (doc) => {
    try {
      await documentApi.relocate({
        document_ids: [doc.id],
        target_rack_id: box.rack_id,
        target_box_id: box.id,
        alasan: `Stock Opname Fisik: Ditemukan di dalam ${box.nomor_box}`,
      });

      // Pindahkan dari misplacedDocs ke matchedDocs
      setMisplacedDocs((prev) => prev.filter((m) => m.id !== doc.id));

      const reconciledDoc = {
        id: doc.id,
        nomor_pb: doc.nomor_pb,
        nama_pemohon: doc.nama_pemohon,
        bagian: doc.bagian,
        status: doc.status || 'Terverifikasi & Diarsipkan',
        scannedAt: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
      };

      setExpectedDocs((prev) => [reconciledDoc, ...prev]);
      setScannedMap((prev) => ({
        ...prev,
        [doc.nomor_pb]: reconciledDoc,
      }));

      playSound('success');
      onToast(`Berkas ${doc.nomor_pb} berhasil direlokasi resmi ke ${box.nomor_box}!`, 'success');
    } catch (err) {
      console.error(err);
      onToast(err.response?.data?.message || 'Gagal merelokasi dokumen.', 'error');
    }
  };

  // Ringkasan Rekonsiliasi Real-time
  const matchedDocsList = useMemo(() => {
    return expectedDocs.filter((d) => !!scannedMap[d.nomor_pb]);
  }, [expectedDocs, scannedMap]);

  const missingDocsList = useMemo(() => {
    return expectedDocs.filter((d) => !scannedMap[d.nomor_pb]);
  }, [expectedDocs, scannedMap]);

  const matchedCount = matchedDocsList.length;
  const missingCount = missingDocsList.length;
  const misplacedCount = misplacedDocs.length;
  const totalExpected = expectedDocs.length;
  const completionPercentage = totalExpected > 0 ? Math.round((matchedCount / totalExpected) * 100) : 100;

  // Sahkan Stock Opname Box ke Backend
  const handleCommitAudit = async () => {
    try {
      setIsSubmitting(true);

      const matchedPbs = matchedDocsList.map((d) => d.nomor_pb);
      const missingPbs = missingDocsList.map((d) => d.nomor_pb);
      const misplacedPbs = misplacedDocs.map((d) => d.nomor_pb);

      const res = await rackApi.commitAudit(box.id, {
        matched_pbs: matchedPbs,
        missing_pbs: missingPbs,
        misplaced_pbs: misplacedPbs,
        catatan: auditNote.trim() || undefined,
      });

      onToast(res.data.message || `Stock Opname ${box.nomor_box} berhasil disahkan!`, 'success');
      if (onSuccess) onSuccess(res.data.box);
      handleClose();
    } catch (err) {
      console.error(err);
      onToast(err.response?.data?.message || 'Gagal mengesahkan hasil stock opname.', 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Reset Sesi Scan
  const handleResetSession = () => {
    if (window.confirm('Apakah Anda yakin ingin mereset sesi pemindaian ini dan mengulang dari awal?')) {
      setScannedMap({});
      setMisplacedDocs([]);
      setGunInput('');
      onToast('Sesi audit fisik telah direset.', 'info');
      if (inputRef.current) inputRef.current.focus();
    }
  };

  const handleClose = () => {
    stopCameraScanning();
    onClose();
  };

  return (
    <div className="fixed inset-0 z-[99999] bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 animate-in fade-in duration-150">
      <div className="bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[94vh] overflow-hidden text-slate-100">
        {/* HEADER: Dark Blueprint Slate High-Contrast Header */}
        <div className="px-5 sm:px-6 py-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between gap-4 flex-shrink-0">
          <div className="flex items-center gap-3 min-w-0">
            <div className="w-11 h-11 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 flex-shrink-0 shadow-inner">
              <ShieldCheck className="w-6 h-6" />
            </div>
            <div className="min-w-0">
              <div className="flex items-center gap-2 flex-wrap">
                <span className="px-2 py-0.5 rounded-md bg-sky-950 border border-sky-500/30 text-sky-300 font-mono text-[10px] font-black uppercase tracking-wider">
                  MODUL AUDIT FISIK GUDANG
                </span>
                <span className="text-xs font-mono font-bold text-slate-400 truncate">
                  {rack?.nama_rak || box.rack_nama}
                </span>
              </div>
              <h3 className="text-base sm:text-lg font-black text-white tracking-tight flex items-center gap-2 mt-0.5 truncate">
                <span>Stock Opname: {box.nomor_box}</span>
                <span className="text-xs font-mono font-semibold text-slate-400 hidden sm:inline">
                  (Ambalan B{box.baris_ke} - Kolom {box.kolom_ke})
                </span>
              </h3>
            </div>
          </div>

          <div className="flex items-center gap-2 flex-shrink-0">
            {/* Audio Beep Indicator Toggle */}
            <button
              type="button"
              onClick={() => setAudioEnabled(!audioEnabled)}
              className={`p-2 rounded-xl text-xs font-bold transition-all border ${
                audioEnabled
                  ? 'bg-emerald-950/60 border-emerald-500/40 text-emerald-300 hover:bg-emerald-900/60'
                  : 'bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700'
              }`}
              title={audioEnabled ? 'Bunyi Beep 880Hz Aktif (Klik untuk Matikan)' : 'Bunyi Beep Mati (Klik untuk Nyalakan)'}
            >
              {audioEnabled ? <Volume2 className="w-4 h-4 text-emerald-400" /> : <VolumeX className="w-4 h-4 text-slate-400" />}
            </button>

            {/* Close Button */}
            <button
              onClick={handleClose}
              className="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
              title="Tutup Sesi Audit (Esc)"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* SCANNER CONSOLE (Input Bar Rapid Scan & Camera View) */}
        <div className="p-4 sm:p-5 bg-slate-900/90 border-b border-slate-800 space-y-3 flex-shrink-0">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            {/* Mode Switcher Buttons */}
            <div className="inline-flex p-1 bg-slate-950 rounded-xl border border-slate-800 text-xs font-bold self-start">
              <button
                type="button"
                onClick={() => setScanMode('gun')}
                className={`px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all ${
                  scanMode === 'gun'
                    ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20'
                    : 'text-slate-400 hover:text-slate-200'
                }`}
              >
                <Zap className="w-3.5 h-3.5" />
                <span>Barcode Gun USB</span>
              </button>
              <button
                type="button"
                onClick={() => setScanMode('camera')}
                className={`px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all ${
                  scanMode === 'camera'
                    ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20'
                    : 'text-slate-400 hover:text-slate-200'
                }`}
              >
                <Camera className="w-3.5 h-3.5" />
                <span>Kamera WebRTC</span>
              </button>
            </div>

            {/* Audit Status Timestamp Badge */}
            <div className="text-[11px] font-mono text-slate-400 flex items-center gap-1.5">
              <Clock className="w-3.5 h-3.5 text-slate-500" />
              <span>Audit Terakhir: </span>
              <strong className="text-slate-200">
                {box.last_audited_formatted || 'Belum Pernah Diaudit'}
              </strong>
            </div>
          </div>

          {/* Barcode Gun Rapid Input Bar */}
          {scanMode === 'gun' ? (
            <div className="relative">
              <div className="absolute left-4 top-1/2 -translate-y-1/2 text-sky-400 flex items-center gap-2 pointer-events-none">
                <Barcode className="w-5 h-5" />
              </div>
              <input
                ref={inputRef}
                type="text"
                value={gunInput}
                onChange={(e) => setGunInput(e.target.value)}
                onKeyDown={handleGunKeyDown}
                placeholder="Arahkan Barcode Gun lalu tembak barcode PB, atau ketik No. PB lalu tekan Enter..."
                className="w-full pl-12 pr-28 py-3 rounded-2xl bg-slate-950 border-2 border-sky-500/60 focus:border-sky-400 text-white placeholder:text-slate-500 text-xs sm:text-sm font-mono font-bold focus:outline-none focus:ring-4 focus:ring-sky-500/20 transition-all shadow-inner"
              />
              <button
                type="button"
                disabled={!gunInput.trim() || isProcessing}
                onClick={() => processScannedBarcode(gunInput)}
                className="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-white text-xs font-bold disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow"
              >
                {isProcessing ? 'Mengecek...' : 'Input (Enter)'}
              </button>
            </div>
          ) : (
            /* Camera Scanner Feed Container */
            <div className="space-y-2">
              <div className="relative bg-black rounded-2xl overflow-hidden max-h-52 flex items-center justify-center border border-slate-800">
                <div id="stock-opname-reader" className="w-full max-w-sm" />
                {cameraError && (
                  <div className="absolute inset-0 bg-slate-950/90 flex flex-col items-center justify-center p-4 text-center">
                    <AlertTriangle className="w-8 h-8 text-rose-500 mb-2" />
                    <p className="text-xs text-rose-300 font-semibold">{cameraError}</p>
                    <button
                      type="button"
                      onClick={() => setScanMode('gun')}
                      className="mt-3 px-3 py-1.5 rounded-xl bg-slate-800 text-slate-200 text-xs font-bold"
                    >
                      Beralih ke Barcode Gun USB
                    </button>
                  </div>
                )}
              </div>

              {cameras.length > 1 && (
                <div className="flex items-center gap-2 text-xs">
                  <span className="text-slate-400">Pilih Kamera:</span>
                  <select
                    value={selectedCameraId}
                    onChange={(e) => {
                      setSelectedCameraId(e.target.value);
                      startCameraScanning(e.target.value);
                    }}
                    className="bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-lg px-2.5 py-1 focus:outline-none"
                  >
                    {cameras.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.label || `Camera ${c.id}`}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>
          )}
        </div>

        {/* 3 REAL-TIME RECONCILIATION KPI SUMMARY CARDS */}
        <div className="px-5 py-3 bg-slate-950 border-b border-slate-800 grid grid-cols-3 gap-2.5 sm:gap-4 flex-shrink-0">
          {/* 1. Cocok / Sesuai */}
          <div
            onClick={() => setActiveTab('matched')}
            className={`p-3 rounded-2xl border transition-all cursor-pointer ${
              activeTab === 'matched' || activeTab === 'all'
                ? 'bg-emerald-950/40 border-emerald-500/50 shadow-lg shadow-emerald-950/30'
                : 'bg-slate-900/50 border-slate-800 opacity-70 hover:opacity-100'
            }`}
          >
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1">
                <CheckCircle2 className="w-3.5 h-3.5" />
                <span>Cocok / Ada</span>
              </span>
              <span className="text-xs font-mono font-black text-emerald-300">
                {completionPercentage}%
              </span>
            </div>
            <div className="text-xl sm:text-2xl font-mono font-black text-emerald-200 mt-1">
              {matchedCount} <span className="text-xs font-normal text-emerald-400/80">/ {totalExpected} Map</span>
            </div>
            <div className="w-full bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
              <div
                className="bg-emerald-500 h-full rounded-full transition-all duration-300"
                style={{ width: `${completionPercentage}%` }}
              />
            </div>
          </div>

          {/* 2. Belum Ter-scan / Hilang */}
          <div
            onClick={() => setActiveTab('missing')}
            className={`p-3 rounded-2xl border transition-all cursor-pointer ${
              activeTab === 'missing'
                ? 'bg-rose-950/50 border-rose-500/50 shadow-lg shadow-rose-950/30'
                : 'bg-slate-900/50 border-slate-800 opacity-70 hover:opacity-100'
            }`}
          >
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-rose-400 flex items-center gap-1">
                <AlertTriangle className="w-3.5 h-3.5" />
                <span>Belum Ter-scan</span>
              </span>
              <span className="text-[10px] font-mono font-bold text-slate-400">
                {missingCount === 0 ? 'Lengkap' : 'Perlu Dicari'}
              </span>
            </div>
            <div className="text-xl sm:text-2xl font-mono font-black text-rose-200 mt-1">
              {missingCount} <span className="text-xs font-normal text-rose-400/80">Map</span>
            </div>
            <div className="text-[10px] text-slate-400 mt-2 truncate">
              {missingCount === 0 ? 'Semua berkas box terverifikasi' : 'Menunggu pemindaian fisik'}
            </div>
          </div>

          {/* 3. Salah Box / Nyasar */}
          <div
            onClick={() => setActiveTab('misplaced')}
            className={`p-3 rounded-2xl border transition-all cursor-pointer ${
              activeTab === 'misplaced'
                ? 'bg-purple-950/50 border-purple-500/50 shadow-lg shadow-purple-950/30'
                : 'bg-slate-900/50 border-slate-800 opacity-70 hover:opacity-100'
            }`}
          >
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-purple-400 flex items-center gap-1">
                <ArrowRightLeft className="w-3.5 h-3.5" />
                <span>Salah Box</span>
              </span>
              <span className="text-[10px] font-mono font-bold text-purple-300">
                {misplacedCount > 0 ? 'Nyasar' : 'Nihil'}
              </span>
            </div>
            <div className="text-xl sm:text-2xl font-mono font-black text-purple-200 mt-1">
              {misplacedCount} <span className="text-xs font-normal text-purple-400/80">Map</span>
            </div>
            <div className="text-[10px] text-slate-400 mt-2 truncate">
              {misplacedCount > 0 ? 'Dapat direlokasi 1-klik' : 'Tidak ada berkas luar'}
            </div>
          </div>
        </div>

        {/* TAB FILTER SELECTOR FOR LIST VIEW */}
        <div className="px-5 py-2 bg-slate-900 border-b border-slate-800 flex items-center justify-between text-xs font-bold text-slate-400">
          <div className="flex items-center gap-1.5 flex-wrap">
            <button
              type="button"
              onClick={() => setActiveTab('all')}
              className={`px-3 py-1 rounded-lg transition-all ${
                activeTab === 'all' ? 'bg-slate-800 text-white' : 'hover:text-slate-200'
              }`}
            >
              Semua Status ({totalExpected + misplacedCount})
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('matched')}
              className={`px-3 py-1 rounded-lg transition-all ${
                activeTab === 'matched' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'hover:text-slate-200'
              }`}
            >
              ✓ Cocok ({matchedCount})
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('missing')}
              className={`px-3 py-1 rounded-lg transition-all ${
                activeTab === 'missing' ? 'bg-rose-950 text-rose-300 border border-rose-500/30' : 'hover:text-slate-200'
              }`}
            >
              ⚠ Belum Ter-scan ({missingCount})
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('misplaced')}
              className={`px-3 py-1 rounded-lg transition-all ${
                activeTab === 'misplaced' ? 'bg-purple-950 text-purple-300 border border-purple-500/30' : 'hover:text-slate-200'
              }`}
            >
              ⇄ Salah Box ({misplacedCount})
            </button>
          </div>

          <div className="hidden sm:flex items-center gap-2 text-[11px] font-mono text-slate-500">
            <span>Ambalan B{box.baris_ke} • Kolom {box.kolom_ke}</span>
          </div>
        </div>

        {/* RECONCILIATION MANIFEST LIST (3-COLUMN RESPONSIVE LAYOUT WITHOUT CONFUSING DOUBLE-SCROLL) */}
        <div className="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {/* COLUMN 1: Cocok / Sesuai (Hijau) */}
            {(activeTab === 'all' || activeTab === 'matched') && (
              <div className="space-y-2.5">
                <div className="flex items-center justify-between text-xs font-bold text-emerald-400 px-1">
                  <span className="flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                    <CheckCircle2 className="w-4 h-4" />
                    <span>Berkas Cocok ({matchedCount})</span>
                  </span>
                  <span className="text-[10px] font-mono text-slate-400">Verifikasi Fisik</span>
                </div>

                <div className="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                  {matchedDocsList.length === 0 ? (
                    <div className="p-6 rounded-2xl bg-slate-950/50 border border-dashed border-slate-800 text-center text-slate-500 text-xs">
                      Belum ada berkas fisik yang berhasil dipindai cocok.
                    </div>
                  ) : (
                    matchedDocsList.map((doc) => (
                      <div
                        key={doc.id}
                        className="p-3 rounded-2xl bg-slate-950 border border-emerald-500/30 text-xs space-y-1.5 hover:border-emerald-500/60 transition-all shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono font-black text-emerald-300">
                            {doc.nomor_pb}
                          </span>
                          <span className="px-2 py-0.5 rounded-full bg-emerald-950 border border-emerald-500/40 text-emerald-300 text-[10px] font-bold">
                            ✓ Fisik Ada
                          </span>
                        </div>
                        <div className="flex items-center justify-between text-[11px] text-slate-300">
                          <span className="truncate">{doc.nama_pemohon}</span>
                          <span className="text-slate-500">{doc.bagian}</span>
                        </div>
                        <div className="pt-1 border-t border-slate-800/80 flex items-center justify-between text-[10px] text-slate-500 font-mono">
                          <span>Waktu Scan: {scannedMap[doc.nomor_pb]?.scannedAt || 'Baru Saja'}</span>
                          {doc.is_borrowed && (
                            <span className="text-amber-400 font-bold">Status Sistem: Dipinjam</span>
                          )}
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}

            {/* COLUMN 2: Belum Ter-scan / Hilang dari Box (Merah/Amber) */}
            {(activeTab === 'all' || activeTab === 'missing') && (
              <div className="space-y-2.5">
                <div className="flex items-center justify-between text-xs font-bold text-rose-400 px-1">
                  <span className="flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                    <AlertTriangle className="w-4 h-4" />
                    <span>Belum Ter-scan ({missingCount})</span>
                  </span>
                  <span className="text-[10px] font-mono text-slate-400">Tercatat di Box</span>
                </div>

                <div className="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                  {missingDocsList.length === 0 ? (
                    <div className="p-6 rounded-2xl bg-emerald-950/20 border border-emerald-500/30 text-center text-emerald-300 text-xs">
                      ✓ Sempurna! Semua berkas tercatat telah berhasil diverifikasi fisiknya.
                    </div>
                  ) : (
                    missingDocsList.map((doc) => (
                      <div
                        key={doc.id}
                        className={`p-3 rounded-2xl bg-slate-950 border text-xs space-y-1.5 transition-all shadow-sm ${
                          doc.is_borrowed
                            ? 'border-amber-500/40 hover:border-amber-500/60'
                            : 'border-rose-500/40 hover:border-rose-500/70'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono font-bold text-slate-200">
                            {doc.nomor_pb}
                          </span>
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold border ${
                              doc.is_borrowed
                                ? 'bg-amber-950/70 border-amber-500/40 text-amber-300'
                                : 'bg-rose-950/70 border-rose-500/40 text-rose-300'
                            }`}
                          >
                            {doc.is_borrowed ? 'Dipinjam Sah' : 'Belum Ter-scan'}
                          </span>
                        </div>

                        <div className="flex items-center justify-between text-[11px] text-slate-300">
                          <span className="truncate">{doc.nama_pemohon}</span>
                          <span className="text-slate-500">{doc.bagian}</span>
                        </div>

                        {doc.active_borrowing && (
                          <div className="p-2 rounded-xl bg-amber-950/40 border border-amber-500/20 text-[10px] text-amber-300/90 leading-tight">
                            Dipinjam oleh: <strong>{doc.active_borrowing.nama_peminjam}</strong> ({doc.active_borrowing.departemen_peminjam})
                            <div className="text-amber-400 font-mono mt-0.5">
                              Batas: {doc.active_borrowing.batas_kembali}
                            </div>
                          </div>
                        )}
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}

            {/* COLUMN 3: Salah Box / Nyasar (Ungu/Kuning) */}
            {(activeTab === 'all' || activeTab === 'misplaced') && (
              <div className="space-y-2.5">
                <div className="flex items-center justify-between text-xs font-bold text-purple-400 px-1">
                  <span className="flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                    <ArrowRightLeft className="w-4 h-4" />
                    <span>Salah Box / Nyasar ({misplacedCount})</span>
                  </span>
                  <span className="text-[10px] font-mono text-slate-400">Tindakan Cepat</span>
                </div>

                <div className="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                  {misplacedDocs.length === 0 ? (
                    <div className="p-6 rounded-2xl bg-slate-950/50 border border-dashed border-slate-800 text-center text-slate-500 text-xs">
                      Tidak ada dokumen nyasar yang terdeteksi di dalam box ini.
                    </div>
                  ) : (
                    misplacedDocs.map((doc) => (
                      <div
                        key={doc.id}
                        className="p-3 rounded-2xl bg-slate-950 border border-purple-500/40 text-xs space-y-2 hover:border-purple-500/70 transition-all shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono font-black text-purple-300">
                            {doc.nomor_pb}
                          </span>
                          <span className="px-2 py-0.5 rounded-full bg-purple-950 border border-purple-500/40 text-purple-300 text-[10px] font-bold">
                            ⇄ Salah Box
                          </span>
                        </div>

                        <div className="text-[11px] text-slate-300">
                          <div>Pemohon: <strong>{doc.nama_pemohon}</strong> ({doc.bagian})</div>
                          <div className="text-amber-300 font-mono text-[10px] mt-0.5">
                            Tercatat di: {doc.lokasi_rak || 'Rak ?'} - {doc.nomor_box || 'Box ?'}
                          </div>
                        </div>

                        {/* 1-Click Action Button: Relokasi ke Box ini */}
                        <div className="pt-2 border-t border-slate-800 flex items-center justify-between gap-2">
                          <button
                            type="button"
                            onClick={() => handleRelocateToThisBox(doc)}
                            className="w-full py-1.5 px-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-[11px] flex items-center justify-center gap-1.5 transition-all shadow-sm"
                            title="Pindahkan berkas ini ke Box ini secara resmi di sistem"
                          >
                            <ArrowRightLeft className="w-3.5 h-3.5" />
                            <span>Relokasi ke Box Ini</span>
                          </button>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}
          </div>

          {/* Catatan Audit Opsional */}
          <div className="p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-1.5">
            <label className="text-[11px] font-bold text-slate-400 block">
              Catatan Stock Opname / Temuan Fisik (Opsional):
            </label>
            <input
              type="text"
              value={auditNote}
              onChange={(e) => setAuditNote(e.target.value)}
              placeholder="Contoh: Berkas fisik nomor PB/BMS-WH/... robek sedikit pada cover, seluruh isi ordner telah diurutkan..."
              className="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-200 placeholder:text-slate-600 focus:outline-none focus:border-sky-500"
            />
          </div>
        </div>

        {/* FOOTER ACTION BAR: Large touch-friendly audit confirmation button */}
        <div className="px-5 sm:px-6 py-4 bg-slate-950 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-shrink-0">
          <div className="flex items-center gap-2 text-xs">
            <button
              type="button"
              onClick={handleResetSession}
              className="px-3 py-2 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 font-bold text-xs flex items-center gap-1.5 transition-colors"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              <span>Reset Sesi</span>
            </button>
            <span className="text-[11px] text-slate-500 hidden sm:inline">
              {matchedCount} dari {totalExpected} map terverifikasi cocok ({completionPercentage}%)
            </span>
          </div>

          <div className="flex items-center gap-2.5">
            <button
              type="button"
              onClick={handleClose}
              className="px-4 py-2.5 rounded-2xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-xs font-bold transition-colors"
            >
              Batal
            </button>

            <button
              type="button"
              disabled={isSubmitting}
              onClick={handleCommitAudit}
              className="px-6 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/50 transition-all disabled:opacity-50"
            >
              <ShieldCheck className="w-4 h-4" />
              <span>{isSubmitting ? 'Mengesahkan Audit...' : '✓ Sahkan Stock Opname Box'}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
