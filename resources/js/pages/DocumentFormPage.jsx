import React, { useState, useEffect, useRef } from 'react';
import {
  FileText,
  Upload,
  Plus,
  Trash2,
  ArrowLeft,
  ArrowRight,
  Layers,
  Save,
  CheckCircle2,
  AlertTriangle,
  Sparkles,
  Paperclip,
  X,
  Copy,
  Calendar,
  Building2,
  User,
  Hash,
  Check,
  PackageCheck,
  FileCheck2,
} from 'lucide-react';
import { documentApi, rackApi } from '../api';

export default function DocumentFormPage({ documentId, onNavigate, onToast }) {
  const isEdit = Boolean(documentId);

  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(isEdit);
  const [generatingNo, setGeneratingNo] = useState(false);

  // Active step: 1 (Data PB) | 2 (Fisik Rak & Box) | 3 (Berkas Digital) | 4 (Item Barang)
  const [activeStep, setActiveStep] = useState(1);

  // Drag & drop state
  const [isDraggingPrimary, setIsDraggingPrimary] = useState(false);

  // Form Fields
  const [nomorPb, setNomorPb] = useState('');
  const [userEditedNo, setUserEditedNo] = useState(false);
  const [tanggalPb, setTanggalPb] = useState(new Date().toISOString().split('T')[0]);
  const [bagian, setBagian] = useState('Workshop Machining & Threading');
  const [namaPemohon, setNamaPemohon] = useState('');
  const [nipPemohon, setNipPemohon] = useState('');
  const [status, setStatus] = useState('Terverifikasi & Diarsipkan');
  const [lokasiRak, setLokasiRak] = useState('Rak A');
  const [nomorBox, setNomorBox] = useState('Box 01');
  const [catatanArsip, setCatatanArsip] = useState('');
  const [catatanVerifikasi, setCatatanVerifikasi] = useState('');
  const [keterangan, setKeterangan] = useState('');

  // Primary file
  const [fileDokumen, setFileDokumen] = useState(null);
  const [jenisDokumenUtama, setJenisDokumenUtama] = useState('Form PB Scan Asli');

  // Additional Multi-Attachments (for new docs)
  const [additionalFiles, setAdditionalFiles] = useState([]);

  // Racks data from API
  const [racksData, setRacksData] = useState([]);

  // Items
  const [items, setItems] = useState([
    {
      nama: '',
      part_number: '',
      jumlah: 1,
      satuan: 'Pcs',
      harga_satuan: 0,
      spesifikasi: '',
    },
  ]);

  const fileInputRef = useRef(null);

  const steps = [
    { id: 1, title: 'Data Pokok PB', subtitle: 'Nomor, tanggal & pemohon', icon: FileText },
    { id: 2, title: 'Alokasi Fisik Rak & Box', subtitle: 'Lokasi ordner di gudang', icon: Layers },
    { id: 3, title: 'Berkas & Lampiran', subtitle: 'Scan PB, PO, DO, BAST', icon: Paperclip },
    { id: 4, title: 'Rincian Material PB', subtitle: 'Spesifikasi & anggaran', icon: PackageCheck },
  ];

  const bagianList = [
    { name: 'Workshop Machining & Threading', code: 'BMS-MCH' },
    { name: 'NDT & Tubular Inspection', code: 'BMS-NDT' },
    { name: 'Warehouse & Logistik', code: 'BMS-WH' },
    { name: 'HSE & K3L', code: 'BMS-HSE' },
    { name: 'Maintenance & Utility', code: 'BMS-MTN' },
    { name: 'Operasional Lapangan', code: 'BMS-OPS' },
    { name: 'Quality Assurance / QC', code: 'BMS-QC' },
  ];

  const statusList = [
    {
      id: 'Terverifikasi & Diarsipkan',
      title: 'Terverifikasi & Diarsipkan',
      desc: 'Berkas fisik sudah ditaruh di ordner box rak gudang (Rekomendasi)',
    },
    {
      id: 'Menunggu Arsip Fisik',
      title: 'Menunggu Berkas Fisik',
      desc: 'Hanya simpan digital sementara, berkas fisik asli belum diserahkan ke rak',
    },
  ];

  const itemPresets = [
    { nama: 'Drill Pipe Thread Compound (Kopr-Kote)', part_number: 'KK-500-DRUM', satuan: 'Drum', harga_satuan: 4500000, spesifikasi: 'High temp copper anti-seize API RP 7A1' },
    { nama: 'Hardfacing Tungsten Carbide Wire 1.6mm', part_number: 'TCW-BMS-16', satuan: 'Roll', harga_satuan: 2850000, spesifikasi: 'For drill collar & stabilizer hardband' },
    { nama: 'O-Ring HNBR 90 Durometer High Pressure Kit', part_number: 'ORK-HNBR-90', satuan: 'Set', harga_satuan: 1350000, spesifikasi: 'Resistant to sour gas H2S and heat' },
    { nama: 'Insert Lathe Turning Sandvik CNMG 120408', part_number: 'SNV-CNMG-12', satuan: 'Box', harga_satuan: 850000, spesifikasi: 'Grade GC4325 for heavy machining' },
    { nama: 'Pressure Gauge 0-10000 PSI 4 inch NPT', part_number: 'PG-10K-SS', satuan: 'Pcs', harga_satuan: 950000, spesifikasi: 'Stainless Steel Case glycerine filled' },
    { nama: 'Stud Bolt B7 Heavy Hex Nut 2H 1x8 inch', part_number: 'SB-B7-1X8', satuan: 'Pcs', harga_satuan: 185000, spesifikasi: 'ASTM A193 B7 / A194 2H PTFE coated' },
  ];

  // Load racks & boxes
  useEffect(() => {
    rackApi.getRacks().then((res) => {
      setRacksData(res.data);
    }).catch(console.error);
  }, []);

  // Load existing doc if editing
  useEffect(() => {
    if (isEdit) {
      setFetching(true);
      documentApi.getById(documentId).then((res) => {
        const d = res.data;
        setNomorPb(d.nomor_pb);
        setTanggalPb(d.tanggal_pb);
        setBagian(d.bagian);
        setNamaPemohon(d.nama_pemohon);
        setNipPemohon(d.nip_pemohon || '');
        setStatus(d.status);
        setCatatanVerifikasi(d.catatan_verifikasi || '');
        setLokasiRak(d.lokasi_rak || 'Rak A');
        setNomorBox(d.nomor_box || 'Box 01');
        setKeterangan(d.keterangan || '');
        setCatatanArsip(d.catatan_arsip || '');
        if (d.items && d.items.length > 0) {
          setItems(d.items);
        }
      }).catch((err) => {
        console.error(err);
        onToast('Gagal memuat data dokumen.', 'error');
      }).finally(() => {
        setFetching(false);
      });
    }
  }, [documentId, isEdit]);

  // Current rack & its boxes
  const currentRack = racksData.find((r) => r.kode_rak === lokasiRak);
  const availableBoxes = currentRack?.boxes || [];
  const selectedBox = availableBoxes.find((b) => b.nomor_box === nomorBox);

  // Auto-generate next PB number function
  const fetchAutoNumber = async (selectedDept = bagian, selectedDate = tanggalPb, showToast = false) => {
    try {
      setGeneratingNo(true);
      const res = await documentApi.generateNumber({ bagian: selectedDept, tanggal: selectedDate });
      if (res.data?.suggested_nomor_pb) {
        setNomorPb(res.data.suggested_nomor_pb);
        if (showToast) {
          onToast(`Nomor PB otomatis: ${res.data.suggested_nomor_pb}`, 'success');
        }
      }
    } catch (err) {
      console.error(err);
      if (showToast) {
        onToast('Gagal menghasilkan nomor otomatis.', 'error');
      }
    } finally {
      setGeneratingNo(false);
    }
  };

  // Automatically generate on initial load and when dept / date changes
  useEffect(() => {
    if (!isEdit && !userEditedNo) {
      fetchAutoNumber(bagian, tanggalPb, false);
    }
  }, [bagian, tanggalPb, isEdit, userEditedNo]);

  const handleGenerateNumber = () => {
    setUserEditedNo(false);
    fetchAutoNumber(bagian, tanggalPb, true);
  };

  // Item helpers
  const handleAddItem = (preset = null) => {
    if (preset) {
      setItems([
        ...items,
        {
          nama: preset.nama,
          part_number: preset.part_number,
          jumlah: 1,
          satuan: preset.satuan,
          harga_satuan: preset.harga_satuan,
          spesifikasi: preset.spesifikasi,
        },
      ]);
      onToast(`Item "${preset.nama}" ditambahkan.`, 'info');
    } else {
      setItems([
        ...items,
        { nama: '', part_number: '', jumlah: 1, satuan: 'Pcs', harga_satuan: 0, spesifikasi: '' },
      ]);
    }
  };

  const handleDuplicateItem = (index) => {
    const itemToCopy = { ...items[index] };
    const updated = [...items];
    updated.splice(index + 1, 0, itemToCopy);
    setItems(updated);
    onToast('Baris barang berhasil diduplikasi.', 'info');
  };

  const handleRemoveItem = (index) => {
    if (items.length <= 1) {
      onToast('Minimal harus ada 1 item barang dalam dokumen PB.', 'warning');
      return;
    }
    setItems(items.filter((_, i) => i !== index));
  };

  const handleItemChange = (index, field, value) => {
    const updated = [...items];
    updated[index][field] = value;
    setItems(updated);
  };

  const calculateTotal = () => {
    return items.reduce((acc, curr) => {
      const j = parseFloat(curr.jumlah) || 0;
      const h = parseFloat(curr.harga_satuan) || 0;
      return acc + (j * h);
    }, 0);
  };

  // Additional file handlers
  const handleAddAdditionalFiles = (e) => {
    const files = Array.from(e.target.files);
    const newEntries = files.map((f) => ({
      id: Math.random().toString(36).substr(2, 9),
      file: f,
      jenis: 'Purchase Order (PO)',
    }));
    setAdditionalFiles([...additionalFiles, ...newEntries]);
    e.target.value = null;
  };

  const handleRemoveAdditionalFile = (id) => {
    setAdditionalFiles(additionalFiles.filter((f) => f.id !== id));
  };

  const handleAdditionalTypeChange = (id, newType) => {
    setAdditionalFiles(
      additionalFiles.map((f) => (f.id === id ? { ...f, jenis: newType } : f))
    );
  };

  // Drag and drop handlers
  const handleDragOver = (e) => {
    e.preventDefault();
    setIsDraggingPrimary(true);
  };

  const handleDragLeave = () => {
    setIsDraggingPrimary(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setIsDraggingPrimary(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const file = e.dataTransfer.files[0];
      setFileDokumen(file);
      onToast(`File "${file.name}" terpilih sebagai formulir utama.`, 'info');
    }
  };

  const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return bytes + ' B';
  };

  // Step advancement validation
  const handleNextStep = () => {
    if (activeStep === 1) {
      if (!nomorPb.trim()) {
        onToast('Nomor Dokumen PB wajib diisi sebelum lanjut.', 'warning');
        return;
      }
      if (!namaPemohon.trim()) {
        onToast('Nama Pemohon wajib diisi sebelum lanjut.', 'warning');
        return;
      }
    }
    if (activeStep < 4) {
      setActiveStep(activeStep + 1);
    }
  };

  const handlePrevStep = () => {
    if (activeStep > 1) {
      setActiveStep(activeStep - 1);
    }
  };

  // Final Submit Handler
  const handleSubmit = async (e) => {
    if (e) e.preventDefault();

    // Validations
    if (!nomorPb.trim()) {
      onToast('Nomor Dokumen PB wajib diisi!', 'warning');
      setActiveStep(1);
      return;
    }
    if (!namaPemohon.trim()) {
      onToast('Nama Pemohon wajib diisi!', 'warning');
      setActiveStep(1);
      return;
    }
    if (items.some((it) => !it.nama.trim())) {
      onToast('Nama barang pada rincian material tidak boleh kosong!', 'warning');
      setActiveStep(4);
      return;
    }

    setLoading(true);

    try {
      if (isEdit) {
        const statusToSubmit = status === 'Perlu Revisi Fisik' ? 'Menunggu Arsip Fisik' : status;

        const res = await documentApi.update(documentId, {
          nomor_pb: nomorPb,
          tanggal_pb: tanggalPb,
          bagian,
          nama_pemohon: namaPemohon,
          nip_pemohon: nipPemohon,
          status: statusToSubmit,
          lokasi_rak: lokasiRak,
          nomor_box: nomorBox,
          keterangan,
          catatan_arsip: catatanArsip,
          items,
        });

        onToast(
          status === 'Perlu Revisi Fisik'
            ? 'Dokumen berhasil diperbaiki dan diajukan ulang ke gudang (Menunggu Arsip Fisik).'
            : (res.data.message || 'Dokumen berhasil diperbarui.'),
          'success'
        );
        onNavigate('document-detail', documentId);
      } else {
        const formData = new FormData();
        formData.append('nomor_pb', nomorPb);
        formData.append('tanggal_pb', tanggalPb);
        formData.append('bagian', bagian);
        formData.append('nama_pemohon', namaPemohon);
        formData.append('nip_pemohon', nipPemohon || '');
        formData.append('status', status);
        formData.append('lokasi_rak', lokasiRak);
        formData.append('nomor_box', nomorBox);
        formData.append('keterangan', keterangan || '');
        formData.append('catatan_arsip', catatanArsip || '');

        if (fileDokumen) {
          formData.append('file_dokumen', fileDokumen);
          formData.append('jenis_dokumen_utama', jenisDokumenUtama);
        }

        // Additional files
        additionalFiles.forEach((af, idx) => {
          formData.append(`additional_attachments[${idx}]`, af.file);
          formData.append(`additional_types[${idx}]`, af.jenis);
        });

        // Items
        items.forEach((item, idx) => {
          formData.append(`items[${idx}][nama]`, item.nama);
          formData.append(`items[${idx}][part_number]`, item.part_number || '');
          formData.append(`items[${idx}][jumlah]`, item.jumlah || 1);
          formData.append(`items[${idx}][satuan]`, item.satuan || 'Pcs');
          formData.append(`items[${idx}][harga_satuan]`, item.harga_satuan || 0);
          formData.append(`items[${idx}][spesifikasi]`, item.spesifikasi || '');
        });

        const res = await documentApi.create(formData);
        onToast(res.data.message || 'Dokumen PB berhasil diarsipkan ke gudang.', 'success');
        onNavigate('documents');
      }
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.message || 'Gagal menyimpan dokumen. Periksa inputan Anda.';
      onToast(msg, 'error');
    } finally {
      setLoading(false);
    }
  };

  const isStep1Done = Boolean(nomorPb.trim() && namaPemohon.trim());
  const isStep2Done = Boolean(lokasiRak && nomorBox);
  const isStep3Done = Boolean(fileDokumen || isEdit);
  const isStep4Done = items.length > 0 && items.every((it) => it.nama.trim());

  if (fetching) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[50vh] text-slate-500">
        <div className="w-10 h-10 border-4 border-sky-600 border-t-transparent rounded-full animate-spin mb-3" />
        <span className="text-xs font-bold tracking-wider uppercase">Memuat Berkas PB...</span>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto space-y-5 pb-6">
      {/* Top Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 pb-3">
        <div>
          <button
            onClick={() => onNavigate(isEdit ? 'document-detail' : 'documents', documentId)}
            className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-sky-600 transition-colors"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>{isEdit ? 'Kembali ke Rincian' : 'Kembali ke Daftar Dokumen PB'}</span>
          </button>

          <div className="flex items-center gap-2 mt-1">
            <h2 className="text-xl font-black text-slate-900 tracking-tight">
              {isEdit ? `Edit Dokumen: ${nomorPb}` : 'Upload & Pengarsipan Dokumen PB Baru'}
            </h2>
            <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase ${
              isEdit ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800'
            }`}>
              {isEdit ? 'Mode Edit' : 'Wizard Terpandu'}
            </span>
          </div>
        </div>

        {/* Top Cancel / Quick Save */}
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => onNavigate(isEdit ? 'document-detail' : 'documents', documentId)}
            className="px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-600"
          >
            Batal
          </button>
          {activeStep === 4 ? (
            <button
              type="button"
              onClick={handleSubmit}
              disabled={loading}
              className="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/30 flex items-center gap-1.5 disabled:opacity-50"
            >
              <Save className="w-3.5 h-3.5" />
              <span>{loading ? 'Menyimpan...' : 'Arsipkan Dokumen'}</span>
            </button>
          ) : (
            <button
              type="button"
              onClick={handleNextStep}
              className="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center gap-1.5"
            >
              <span>Lanjut</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          )}
        </div>
      </div>

      {/* Revision Required Notice Banner */}
      {status === 'Perlu Revisi Fisik' && (
        <div className="p-4 rounded-2xl bg-gradient-to-r from-rose-50 to-amber-50 border-2 border-rose-300 text-rose-950 flex items-start gap-3 shadow-sm">
          <div className="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5">
            <AlertTriangle className="w-5 h-5" />
          </div>
          <div className="space-y-1">
            <div className="text-xs font-black uppercase tracking-wide text-rose-900 flex items-center gap-2">
              <span>Berkas Dikembalikan Gudang untuk Revisi Fisik</span>
              <span className="px-2 py-0.5 rounded-full bg-rose-200 text-rose-900 text-[10px] font-bold">
                Perlu Revisi Fisik
              </span>
            </div>
            {catatanVerifikasi && (
              <p className="text-xs text-rose-900 font-semibold">
                Catatan Petugas: <span className="italic font-bold text-rose-950">"{catatanVerifikasi}"</span>
              </p>
            )}
            <p className="text-[11px] text-rose-800">
              Perbaiki informasi atau lampiran berkas yang kurang di bawah ini. Setelah Anda menyimpan formulir ini, status berkas akan otomatis diajukan ulang menjadi <strong>"Menunggu Arsip Fisik"</strong> agar dapat diverifikasi kembali oleh petugas gudang.
            </p>
          </div>
        </div>
      )}

      {/* Stepper Navigation: 4 Distinct Tabs */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        {steps.map((s) => {
          const StepIcon = s.icon;
          const isActive = activeStep === s.id;
          const isDone =
            s.id === 1 ? isStep1Done : s.id === 2 ? isStep2Done : s.id === 3 ? isStep3Done : isStep4Done;

          return (
            <button
              key={s.id}
              type="button"
              onClick={() => setActiveStep(s.id)}
              className={`p-3 rounded-2xl border text-left transition-all relative overflow-hidden flex items-center gap-3 ${
                isActive
                  ? 'bg-slate-900 border-slate-900 text-white shadow-md ring-2 ring-slate-900/10'
                  : 'bg-white border-slate-200 hover:border-slate-300 text-slate-800'
              }`}
            >
              <div
                className={`w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs flex-shrink-0 ${
                  isActive
                    ? 'bg-sky-500 text-white'
                    : isDone
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-slate-100 text-slate-500'
                }`}
              >
                {isDone ? <Check className="w-4 h-4 stroke-[3]" /> : <StepIcon className="w-4 h-4" />}
              </div>

              <div className="min-w-0">
                <div className={`text-xs font-black truncate ${isActive ? 'text-white' : 'text-slate-900'}`}>
                  Langkah {s.id}
                </div>
                <div className={`text-[11px] font-semibold truncate ${isActive ? 'text-slate-200' : 'text-slate-500'}`}>
                  {s.title}
                </div>
              </div>
            </button>
          );
        })}
      </div>

      {/* Main Focused Step Canvas */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {/* STEP CONTENT CONTAINER (Only the active step is rendered - ZERO scrolling!) */}
        <div className="lg:col-span-8 bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm min-h-[460px] flex flex-col justify-between">

          {/* STEP 1: Data Pokok PB */}
          {activeStep === 1 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <FileText className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-sm font-black text-slate-900 uppercase tracking-wider">
                      Langkah 1: Informasi Pokok Permintaan Barang
                    </h3>
                    <p className="text-[11px] text-slate-500">Nomor dokumen resmi, tanggal dan data pemohon</p>
                  </div>
                </div>

                {!isEdit && (
                  <button
                    type="button"
                    onClick={handleGenerateNumber}
                    disabled={generatingNo}
                    className="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm"
                    title="Generate ulang nomor PB otomatis"
                  >
                    <Sparkles className="w-3.5 h-3.5 text-emerald-600 animate-pulse" />
                    <span>{generatingNo ? 'Menyusun...' : '✨ Auto Generated'}</span>
                  </button>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {/* Nomor PB */}
                <div className="sm:col-span-2">
                  <div className="flex items-center justify-between mb-1">
                    <label className="block text-xs font-bold text-slate-700">
                      Nomor Dokumen PB <span className="text-rose-500">*</span>
                    </label>
                    {!isEdit && (
                      <span className="text-[11px] text-slate-500 flex items-center gap-1">
                        {userEditedNo ? (
                          <button
                            type="button"
                            onClick={() => {
                              setUserEditedNo(false);
                              fetchAutoNumber(bagian, tanggalPb, true);
                            }}
                            className="text-sky-600 hover:underline font-bold"
                          >
                            ⚡ Kembalikan ke Auto Generator
                          </button>
                        ) : (
                          <span className="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            ✨ Otomatis Terisi Sesuai Urutan
                          </span>
                        )}
                      </span>
                    )}
                  </div>
                  <div className="relative">
                    <Hash className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                      type="text"
                      required
                      value={nomorPb}
                      onChange={(e) => {
                        setNomorPb(e.target.value);
                        setUserEditedNo(true);
                      }}
                      placeholder="Menghasilkan nomor PB otomatis..."
                      className="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-300 font-mono text-sm font-black text-sky-950 focus:ring-2 focus:ring-sky-500 focus:outline-none bg-slate-50/50"
                    />
                  </div>
                </div>

                {/* Tanggal PB */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">
                    Tanggal Pengajuan PB <span className="text-rose-500">*</span>
                  </label>
                  <div className="relative">
                    <Calendar className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                      type="date"
                      required
                      value={tanggalPb}
                      onChange={(e) => {
                        setTanggalPb(e.target.value);
                        if (!isEdit && !userEditedNo) {
                          fetchAutoNumber(bagian, e.target.value, false);
                        }
                      }}
                      className="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-sky-500 focus:outline-none"
                    />
                  </div>
                </div>

                {/* Departemen */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">
                    Departemen / Bagian <span className="text-rose-500">*</span>
                  </label>
                  <div className="relative">
                    <Building2 className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <select
                      value={bagian}
                      onChange={(e) => {
                        setBagian(e.target.value);
                        if (!isEdit && !userEditedNo) {
                          fetchAutoNumber(e.target.value, tanggalPb, false);
                        }
                      }}
                      className="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-sky-500 focus:outline-none bg-white"
                    >
                      {bagianList.map((b) => (
                        <option key={b.name} value={b.name}>
                          [{b.code}] {b.name}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>

                {/* Nama Pemohon */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">
                    Nama Pemohon Barang <span className="text-rose-500">*</span>
                  </label>
                  <div className="relative">
                    <User className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                      type="text"
                      required
                      value={namaPemohon}
                      onChange={(e) => setNamaPemohon(e.target.value)}
                      placeholder="Contoh: Agus Pratama"
                      className="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                    />
                  </div>
                </div>

                {/* NIP Pemohon */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-1">
                    NIP / ID Karyawan
                  </label>
                  <input
                    type="text"
                    value={nipPemohon}
                    onChange={(e) => setNipPemohon(e.target.value)}
                    placeholder="Contoh: BMS-MCH-0104"
                    className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                  />
                </div>

                {/* Status Selector Pills (Enforced State Machine) */}
                <div className="sm:col-span-2">
                  <label className="block text-xs font-bold text-slate-700 mb-1.5">
                    Kondisi Fisik Pengarsipan <span className="text-rose-500">*</span>
                  </label>

                  {isEdit && status.includes('Dipinjam') ? (
                    <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                      <div className="flex items-center gap-2 font-bold">
                        <span className="w-2 h-2 rounded-full bg-rose-600 animate-pulse" />
                        <span>Status Terkunci: Dokumen Sedang Dipinjam Keluar</span>
                      </div>
                      <p className="text-[11px] text-rose-600 leading-relaxed">
                        Dokumen ini tercatat dalam sirkulasi peminjaman fisik. Perubahan status kembali ke arsip harus dilakukan melalui menu <strong>Sirkulasi Peminjaman &gt; Kembalikan Berkas</strong> setelah verifikasi fisik dilakukan.
                      </p>
                    </div>
                  ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                      {statusList.map((st) => (
                        <div
                          key={st.id}
                          onClick={() => setStatus(st.id)}
                          className={`p-3 rounded-2xl border cursor-pointer transition-all ${
                            status === st.id
                              ? 'border-sky-600 bg-sky-50/80 ring-2 ring-sky-500/20 shadow-sm'
                              : 'border-slate-200 hover:border-slate-300 bg-white'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-0.5">
                            <span className="text-xs font-extrabold text-slate-900">{st.title}</span>
                            {status === st.id && (
                              <span className="w-2 h-2 rounded-full bg-sky-600" />
                            )}
                          </div>
                          <p className="text-[10px] text-slate-500 leading-tight">{st.desc}</p>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Keterangan */}
                <div className="sm:col-span-2">
                  <label className="block text-xs font-bold text-slate-700 mb-1">
                    Keperluan / Catatan Pengadaan
                  </label>
                  <textarea
                    rows={2}
                    value={keterangan}
                    onChange={(e) => setKeterangan(e.target.value)}
                    placeholder="Uraian singkat tujuan pengadaan barang material..."
                    className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                  />
                </div>
              </div>
            </div>
          )}

          {/* STEP 2: Alokasi Fisik Rak & Box Gudang */}
          {activeStep === 2 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <Layers className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-sm font-black text-slate-900 uppercase tracking-wider">
                      Langkah 2: Alokasi Penempatan Berkas Fisik Gudang
                    </h3>
                    <p className="text-[11px] text-slate-500">Pilih lemari rak dan ordner box dengan kapasitas aman</p>
                  </div>
                </div>

                <span className="text-[11px] font-bold text-slate-400">
                  Zona: {currentRack?.zona_lokasi || 'Warehouse'}
                </span>
              </div>

              {/* Rak Selector Tabs */}
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                  Pilih Lemari Rak Arsip:
                </label>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  {racksData.map((r) => {
                    const isSelected = r.kode_rak === lokasiRak;
                    return (
                      <button
                        key={r.id}
                        type="button"
                        onClick={() => {
                          setLokasiRak(r.kode_rak);
                          if (r.boxes && r.boxes.length > 0) {
                            setNomorBox(r.boxes[0].nomor_box);
                          }
                        }}
                        className={`p-2.5 rounded-xl border text-left transition-all ${
                          isSelected
                            ? 'border-sky-600 bg-sky-50 ring-2 ring-sky-500/20'
                            : 'border-slate-200 hover:border-slate-300 bg-white'
                        }`}
                      >
                        <div className="text-xs font-black text-slate-900">{r.kode_rak}</div>
                        <div className="text-[10px] text-slate-500 truncate">{r.nama_rak}</div>
                        <div className="text-[10px] text-sky-700 font-bold mt-0.5">
                          {r.persentase_kapasitas}% Terisi
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Box Selector Grid with Capacity Bars */}
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                  Pilih Ordner Box pada {lokasiRak}:
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  {availableBoxes.map((b) => {
                    const isSelected = b.nomor_box === nomorBox;
                    const isLocked = b.status === 'Penuh (Terkunci)';
                    const isAlmostFull = isLocked || (b.persentase_keterisian || 0) >= 90;
                    const isMedium = (b.persentase_keterisian || 0) >= 70 && !isAlmostFull;

                    return (
                      <div
                        key={b.id}
                        onClick={() => setNomorBox(b.nomor_box)}
                        className={`p-3 rounded-xl border cursor-pointer transition-all ${
                          isSelected
                            ? 'border-sky-600 bg-sky-50/70 ring-2 ring-sky-500/20'
                            : isLocked
                            ? 'border-rose-200 bg-rose-50/40 opacity-75'
                            : 'border-slate-200 hover:border-slate-300 bg-white'
                        }`}
                      >
                        <div className="flex items-center justify-between mb-1">
                          <div className="flex items-center gap-1.5">
                            <span className="font-mono font-black text-xs text-slate-900">{b.nomor_box}</span>
                            {isSelected && (
                              <span className="px-1.5 py-0.2 rounded bg-sky-600 text-white text-[8px] font-black">
                                DIPILIH
                              </span>
                            )}
                          </div>
                          <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${
                            isLocked
                              ? 'bg-rose-600 text-white'
                              : isAlmostFull
                              ? 'bg-rose-100 text-rose-700'
                              : isMedium
                              ? 'bg-amber-100 text-amber-700'
                              : 'bg-emerald-100 text-emerald-700'
                          }`}>
                            {isLocked ? '🔒 Terkunci Penuh' : `${b.total_dokumen}/${b.kapasitas_maksimal} Dok`}
                          </span>
                        </div>

                        {/* Progress Bar */}
                        <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mb-1">
                          <div
                            className={`h-full rounded-full transition-all duration-300 ${
                              isLocked ? 'bg-rose-600' : isAlmostFull ? 'bg-rose-500' : isMedium ? 'bg-amber-500' : 'bg-emerald-500'
                            }`}
                            style={{ width: `${isLocked ? 100 : Math.min(b.persentase_keterisian || 0, 100)}%` }}
                          />
                        </div>

                        <div className="flex items-center justify-between text-[10px] text-slate-500">
                          <span>Sisa kuota: {isLocked ? '0 (Terkunci)' : `${b.sisa_kapasitas ?? (b.kapasitas_maksimal - b.total_dokumen)} slot`}</span>
                          <span className="font-semibold">{b.status}</span>
                        </div>
                      </div>
                    );
                  })}
                </div>

                {selectedBox && selectedBox.status === 'Penuh (Terkunci)' && (
                  <div className="mt-2.5 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11px] flex items-center gap-2">
                    <AlertTriangle className="w-4 h-4 text-rose-600 flex-shrink-0" />
                    <span><strong>Box Terkunci Penuh:</strong> Box ini telah ditandai penuh secara fisik oleh petugas gudang. Sangat disarankan memilih ordner box lain yang masih longgar.</span>
                  </div>
                )}

                {selectedBox && (selectedBox.persentase_keterisian || 0) >= 90 && (
                  <div className="mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] flex items-center gap-2">
                    <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0" />
                    <span>Box ini hampir penuh ({selectedBox.persentase_keterisian}%). Disarankan memilih box lain yang masih longgar.</span>
                  </div>
                )}
              </div>

              {/* Catatan Posisi Fisik */}
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">
                  Catatan Posisi Fisik / Warna Ordner
                </label>
                <input
                  type="text"
                  value={catatanArsip}
                  onChange={(e) => setCatatanArsip(e.target.value)}
                  placeholder="Contoh: Disimpan dalam ordner warna biru tebal posisi baris ke-2"
                  className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                />
              </div>
            </div>
          )}

          {/* STEP 3: Berkas Digital & Multi-Lampiran */}
          {activeStep === 3 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <Paperclip className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-sm font-black text-slate-900 uppercase tracking-wider">
                      Langkah 3: Berkas Digital & Multi-Lampiran
                    </h3>
                    <p className="text-[11px] text-slate-500">Unggah berkas PB scan asli beserta dokumen pendukung (PO, DO, BAST)</p>
                  </div>
                </div>

                <span className="text-[10px] font-bold text-slate-400">PDF / JPG / PNG (Maks 10MB)</span>
              </div>

              {/* Primary File Dropzone */}
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                  Dokumen Scan Form PB Utama:
                </label>

                {fileDokumen ? (
                  <div className="p-3.5 rounded-2xl bg-sky-50 border border-sky-200 flex items-center justify-between">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div className="w-9 h-9 rounded-xl bg-sky-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {fileDokumen.name.endsWith('.pdf') ? 'PDF' : 'IMG'}
                      </div>
                      <div className="min-w-0">
                        <div className="text-xs font-bold text-slate-900 truncate">{fileDokumen.name}</div>
                        <div className="text-[10px] text-slate-500">{formatBytes(fileDokumen.size)} • {jenisDokumenUtama}</div>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <select
                        value={jenisDokumenUtama}
                        onChange={(e) => setJenisDokumenUtama(e.target.value)}
                        className="px-2 py-1 rounded-lg border border-sky-300 text-xs bg-white text-slate-800"
                      >
                        <option value="Form PB Scan Asli">Form PB Scan Asli</option>
                        <option value="Purchase Order (PO)">Purchase Order (PO)</option>
                        <option value="Delivery Order (DO)">Delivery Order (DO)</option>
                        <option value="Berita Acara (BAST)">Berita Acara (BAST)</option>
                      </select>
                      <button
                        type="button"
                        onClick={() => setFileDokumen(null)}
                        className="p-1 rounded-lg text-rose-500 hover:bg-rose-100"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ) : (
                  <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    onClick={() => fileInputRef.current?.click()}
                    className={`border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all ${
                      isDraggingPrimary
                        ? 'border-sky-500 bg-sky-50'
                        : 'border-slate-300 hover:border-sky-400 bg-slate-50/50 hover:bg-white'
                    }`}
                  >
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept=".pdf,.jpg,.jpeg,.png"
                      onChange={(e) => setFileDokumen(e.target.files[0])}
                      className="hidden"
                    />
                    <div className="w-10 h-10 rounded-full bg-white shadow-sm border border-slate-200 text-sky-600 flex items-center justify-center mx-auto mb-2">
                      <Upload className="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <p className="text-xs font-bold text-slate-800">
                      Tarik & Letakkan file scan PB di sini, atau <span className="text-sky-600 underline">Pilih Berkas</span>
                    </p>
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      PDF, JPG, PNG beresolusi tinggi hingga 10MB
                    </p>
                  </div>
                )}
              </div>

              {/* Additional Multi-Attachments */}
              <div className="pt-2 border-t border-slate-100">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <label className="block text-xs font-bold text-slate-700">
                      Dokumen Pendukung Tambahan ({additionalFiles.length} File)
                    </label>
                    <p className="text-[10px] text-slate-400">PO, DO, Surat Jalan, BAST, Sertifikat Kalibrasi</p>
                  </div>

                  <label className="px-2.5 py-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer inline-flex items-center gap-1 shadow-sm">
                    <Plus className="w-3 h-3" />
                    <span>+ Tambah Berkas</span>
                    <input
                      type="file"
                      multiple
                      accept=".pdf,.jpg,.jpeg,.png"
                      onChange={handleAddAdditionalFiles}
                      className="hidden"
                    />
                  </label>
                </div>

                {additionalFiles.length > 0 ? (
                  <div className="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                    {additionalFiles.map((af) => (
                      <div
                        key={af.id}
                        className="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-2 text-xs"
                      >
                        <div className="flex items-center gap-2 min-w-0">
                          <Paperclip className="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                          <div className="min-w-0">
                            <div className="font-semibold text-slate-800 truncate">{af.file.name}</div>
                            <div className="text-[9px] text-slate-500">{formatBytes(af.file.size)}</div>
                          </div>
                        </div>

                        <div className="flex items-center gap-1.5 flex-shrink-0">
                          <select
                            value={af.jenis}
                            onChange={(e) => handleAdditionalTypeChange(af.id, e.target.value)}
                            className="px-2 py-0.5 rounded-lg border border-slate-300 text-[11px] bg-white"
                          >
                            <option value="Purchase Order (PO)">Purchase Order (PO)</option>
                            <option value="Delivery Order (DO)">Delivery Order (DO)</option>
                            <option value="Berita Acara (BAST)">Berita Acara (BAST)</option>
                            <option value="Sertifikat Kalibrasi / Mill">Sertifikat Kalibrasi / Mill</option>
                            <option value="Dokumen Pendukung Lain">Dokumen Pendukung Lain</option>
                          </select>
                          <button
                            type="button"
                            onClick={() => handleRemoveAdditionalFile(af.id)}
                            className="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="p-3 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400">
                    Tidak ada lampiran tambahan. Anda dapat menambahkannya nanti dari rincian dokumen.
                  </div>
                )}
              </div>
            </div>
          )}

          {/* STEP 4: Rincian Barang Diminta & Konfirmasi */}
          {activeStep === 4 && (
            <div className="space-y-3.5">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <PackageCheck className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-sm font-black text-slate-900 uppercase tracking-wider">
                      Langkah 4: Rincian Barang Permintaan ({items.length} Item)
                    </h3>
                    <p className="text-[11px] text-slate-500">Katalog spesifikasi barang dan estimasi anggaran</p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => handleAddItem()}
                  className="px-3 py-1 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                >
                  <Plus className="w-3.5 h-3.5 stroke-[3]" />
                  <span>+ Tambah Baris</span>
                </button>
              </div>

              {/* Presets */}
              <div>
                <span className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block mb-1">
                  Preset Cepat Material Besmindo:
                </span>
                <div className="flex flex-wrap gap-1.5">
                  {itemPresets.map((preset, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => handleAddItem(preset)}
                      className="px-2 py-0.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-sky-50 text-slate-700 text-[10px] font-semibold inline-flex items-center gap-1"
                    >
                      <Plus className="w-2.5 h-2.5 text-sky-600" />
                      <span>{preset.nama.split(' ')[0]} {preset.nama.split(' ')[1]}</span>
                    </button>
                  ))}
                </div>
              </div>

              {/* Items Card List (Compact with scrollbar if many items) */}
              <div className="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
                {items.map((item, idx) => {
                  const subtotal = (parseFloat(item.jumlah) || 0) * (parseFloat(item.harga_satuan) || 0);

                  return (
                    <div
                      key={idx}
                      className="p-3 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2"
                    >
                      <div className="flex items-center justify-between border-b border-slate-200/50 pb-1.5">
                        <div className="flex items-center gap-1.5">
                          <span className="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px] font-black">
                            {idx + 1}
                          </span>
                          <span className="text-xs font-bold text-slate-800">
                            {item.nama ? item.nama : 'Item Baru'}
                          </span>
                        </div>

                        <div className="flex items-center gap-1">
                          <button
                            type="button"
                            onClick={() => handleDuplicateItem(idx)}
                            className="p-1 rounded text-slate-400 hover:text-sky-600"
                            title="Duplikasi"
                          >
                            <Copy className="w-3.5 h-3.5" />
                          </button>
                          {items.length > 1 && (
                            <button
                              type="button"
                              onClick={() => handleRemoveItem(idx)}
                              className="p-1 rounded text-slate-400 hover:text-rose-600"
                              title="Hapus"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          )}
                        </div>
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                        <div className="sm:col-span-5">
                          <input
                            type="text"
                            required
                            value={item.nama}
                            onChange={(e) => handleItemChange(idx, 'nama', e.target.value)}
                            placeholder="Nama part / material..."
                            className="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold bg-white"
                          />
                        </div>

                        <div className="sm:col-span-3">
                          <input
                            type="text"
                            value={item.part_number || ''}
                            onChange={(e) => handleItemChange(idx, 'part_number', e.target.value)}
                            placeholder="Part number..."
                            className="w-full px-2 py-1.5 rounded-lg border border-slate-300 font-mono text-[11px] bg-white text-sky-900"
                          />
                        </div>

                        <div className="sm:col-span-2">
                          <div className="flex items-center gap-1">
                            <input
                              type="number"
                              min={1}
                              value={item.jumlah}
                              onChange={(e) => handleItemChange(idx, 'jumlah', parseInt(e.target.value) || 1)}
                              className="w-12 px-1.5 py-1.5 rounded-lg border border-slate-300 text-xs text-center font-bold bg-white"
                            />
                            <select
                              value={item.satuan || 'Pcs'}
                              onChange={(e) => handleItemChange(idx, 'satuan', e.target.value)}
                              className="px-1 py-1.5 rounded-lg border border-slate-300 text-[11px] bg-white"
                            >
                              {['Pcs', 'Unit', 'Set', 'Drum', 'Roll', 'Meter', 'Batang', 'Box'].map((u) => (
                                <option key={u} value={u}>{u}</option>
                              ))}
                            </select>
                          </div>
                        </div>

                        <div className="sm:col-span-2 text-right">
                          <span className="text-[10px] text-slate-400 block">Subtotal</span>
                          <span className="text-xs font-black text-slate-900">
                            Rp {subtotal.toLocaleString('id-ID')}
                          </span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Total Card */}
              <div className="p-3 rounded-2xl bg-slate-900 text-white flex items-center justify-between">
                <span className="text-xs font-bold text-slate-400">
                  Total Estimasi Anggaran ({items.length} Item):
                </span>
                <span className="text-base font-black text-amber-400">
                  Rp {calculateTotal().toLocaleString('id-ID')}
                </span>
              </div>
            </div>
          )}

          {/* Bottom Step Navigation Bar */}
          <div className="pt-4 border-t border-slate-100 flex items-center justify-between mt-auto">
            {activeStep > 1 ? (
              <button
                type="button"
                onClick={handlePrevStep}
                className="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 flex items-center gap-1.5"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
                <span>Sebelumnya</span>
              </button>
            ) : (
              <button
                type="button"
                onClick={() => onNavigate(isEdit ? 'document-detail' : 'documents', documentId)}
                className="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-600"
              >
                Batal
              </button>
            )}

            {activeStep < 4 ? (
              <button
                type="button"
                onClick={handleNextStep}
                className="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md flex items-center gap-1.5"
              >
                <span>Lanjut ke Langkah {activeStep + 1}</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </button>
            ) : (
              <button
                type="button"
                onClick={handleSubmit}
                disabled={loading}
                className="px-6 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-lg shadow-sky-600/30 flex items-center gap-1.5"
              >
                <Save className="w-4 h-4" />
                <span>{loading ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Finalisasi & Arsipkan PB'}</span>
              </button>
            )}
          </div>
        </div>

        {/* RIGHT SIDEBAR: Live Paspor Arsip Digital (Compact & Persistent) */}
        <div className="lg:col-span-4 space-y-4">
          <div className="bg-white rounded-3xl p-5 border-2 border-slate-900 shadow-lg space-y-3">
            <div className="flex items-center justify-between border-b-2 border-slate-900 pb-2">
              <div>
                <div className="text-[9px] font-black uppercase tracking-wider text-slate-500">
                  PASPOR ARSIP DIGITAL
                </div>
                <div className="text-xs font-black text-slate-900">
                  PT BESMINDO MATERI SEWATAMA
                </div>
              </div>
              <span className="px-2 py-0.5 rounded bg-black text-white text-[9px] font-extrabold font-mono">
                SIP-PB
              </span>
            </div>

            {/* Nomor PB */}
            <div>
              <span className="text-[10px] font-bold text-slate-400 uppercase">Nomor Pengajuan PB</span>
              <div className="font-mono font-black text-sm text-sky-800 break-all leading-tight">
                {nomorPb || 'PB/BMS-WH/YYYY/MM/XXX'}
              </div>
            </div>

            {/* Dept & Tanggal */}
            <div className="grid grid-cols-2 gap-2 text-xs">
              <div className="p-2 rounded-xl bg-slate-50 border border-slate-200">
                <span className="text-[9px] text-slate-400 font-bold block">Tanggal</span>
                <span className="font-bold text-slate-800 text-[11px]">{tanggalPb}</span>
              </div>
              <div className="p-2 rounded-xl bg-slate-50 border border-slate-200">
                <span className="text-[9px] text-slate-400 font-bold block">Pemohon</span>
                <span className="font-bold text-slate-800 text-[11px] truncate block">
                  {namaPemohon || '–'}
                </span>
              </div>
            </div>

            {/* Alokasi Gudang */}
            <div className="p-2.5 rounded-2xl bg-sky-50 border border-sky-200 space-y-1">
              <div className="flex items-center justify-between">
                <span className="text-[9px] font-extrabold text-sky-900 uppercase tracking-wider">
                  Ordner Fisik Gudang:
                </span>
                <span className="font-mono font-black text-xs text-sky-800">
                  {lokasiRak} • {nomorBox}
                </span>
              </div>
              <div className="text-[10px] text-slate-600 truncate">
                {currentRack?.nama_rak || 'Rak Arsip'} ({currentRack?.zona_lokasi || 'Warehouse'})
              </div>
            </div>

            {/* Items & Attachments counter */}
            <div className="grid grid-cols-2 gap-2 text-center text-xs">
              <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <div className="text-lg font-black text-slate-900">{items.length}</div>
                <div className="text-[9px] font-bold text-slate-400 uppercase">Item Barang</div>
              </div>

              <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <div className="text-lg font-black text-slate-900">
                  {(fileDokumen ? 1 : 0) + additionalFiles.length}
                </div>
                <div className="text-[9px] font-bold text-slate-400 uppercase">Berkas Lampiran</div>
              </div>
            </div>

            {/* Total Budget */}
            <div className="border-t border-slate-200 pt-2.5 flex items-center justify-between">
              <span className="text-xs font-bold text-slate-500">Total Anggaran:</span>
              <span className="text-sm font-black text-slate-900">
                Rp {calculateTotal().toLocaleString('id-ID')}
              </span>
            </div>
          </div>

          {/* Quick Step Jump checklist */}
          <div className="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-sm space-y-2">
            <h4 className="text-[10px] font-black uppercase tracking-wider text-slate-400">
              Progres Pengisian Formulir
            </h4>
            <div className="space-y-1.5 text-xs">
              {[
                { id: 1, name: '1. Data Pokok PB', ok: isStep1Done },
                { id: 2, name: '2. Alokasi Rak & Box', ok: isStep2Done },
                { id: 3, name: '3. Berkas Scan & Lampiran', ok: isStep3Done },
                { id: 4, name: '4. Rincian Item Material', ok: isStep4Done },
              ].map((s) => (
                <button
                  key={s.id}
                  type="button"
                  onClick={() => setActiveStep(s.id)}
                  className={`w-full flex items-center justify-between p-2 rounded-xl text-left transition-all ${
                    activeStep === s.id ? 'bg-slate-100 font-bold' : 'hover:bg-slate-50'
                  }`}
                >
                  <span className={activeStep === s.id ? 'text-slate-900' : 'text-slate-600'}>
                    {s.name}
                  </span>
                  {s.ok ? (
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" />
                  ) : (
                    <span className="w-2 h-2 rounded-full bg-slate-300" />
                  )}
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
