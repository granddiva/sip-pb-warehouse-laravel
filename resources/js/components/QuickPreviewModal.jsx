import React from 'react';
import { X, Download, ExternalLink, FileText } from 'lucide-react';

export default function QuickPreviewModal({ file, onClose }) {
  if (!file) return null;

  return (
    <div className="fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-4xl h-[88vh] flex flex-col overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        {/* Header */}
        <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center gap-3 overflow-hidden">
            <div className="p-2 rounded-lg bg-sky-500/20 text-sky-400">
              <FileText className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-sm font-bold truncate text-white">{file.name || 'Pratinjau Dokumen'}</h3>
              <p className="text-xs text-slate-400">{file.type || 'Berkas Lampiran PB'}</p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            {file.downloadUrl && (
              <a
                href={file.downloadUrl}
                download
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200"
              >
                <Download className="w-3.5 h-3.5" />
                <span>Unduh</span>
              </a>
            )}
            <button
              onClick={onClose}
              className="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Preview Frame */}
        <div className="flex-1 bg-slate-100 p-2 overflow-hidden flex items-center justify-center">
          {file.url ? (
            file.isImage ? (
              <img
                src={file.url}
                alt={file.name}
                className="max-h-full max-w-full object-contain rounded shadow"
              />
            ) : (
              <iframe
                src={file.url}
                className="w-full h-full rounded border border-slate-300 bg-white"
                title={file.name}
              />
            )
          ) : (
            <div className="text-center p-8 text-slate-400">
              <FileText className="w-12 h-12 mx-auto mb-3 text-slate-300" />
              <p className="text-sm font-medium">Pratinjau langsung tidak tersedia untuk format berkas ini.</p>
              {file.downloadUrl && (
                <a
                  href={file.downloadUrl}
                  className="mt-3 inline-block text-sky-600 hover:underline text-xs font-bold"
                >
                  Unduh berkas untuk melihat
                </a>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
