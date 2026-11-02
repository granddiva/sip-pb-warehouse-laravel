import React, { useState, useEffect } from 'react';
import { authApi, dashboardApi, borrowingApi } from './api';
import Sidebar from './components/Sidebar';
import Navbar from './components/Navbar';
import ToastContainer from './components/ToastContainer';
import QuickPreviewModal from './components/QuickPreviewModal';
import ReturnModal from './components/ReturnModal';
import BorrowModal from './components/BorrowModal';

// Pages
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import DocumentsPage from './pages/DocumentsPage';
import DocumentDetailPage from './pages/DocumentDetailPage';
import DocumentFormPage from './pages/DocumentFormPage';
import WarehouseRacksPage from './pages/WarehouseRacksPage';
import BorrowingsPage from './pages/BorrowingsPage';
import QrCodePrintPage from './pages/QrCodePrintPage';
import AuditTrailPage from './pages/AuditTrailPage';

export default function App() {
  const [user, setUser] = useState(null);
  const [checkingAuth, setCheckingAuth] = useState(true);

  // Router navigation state
  const [currentPage, setCurrentPage] = useState('dashboard');
  const [selectedDocId, setSelectedDocId] = useState(null);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Global counts for sidebar badges
  const [counts, setCounts] = useState({ documents: 0, borrowings: 0, overdue: 0 });

  // Global Toasts
  const [toasts, setToasts] = useState([]);

  // Modals
  const [previewFile, setPreviewFile] = useState(null);
  const [returnModalData, setReturnModalData] = useState(null);
  const [borrowModalDoc, setBorrowModalDoc] = useState(null);
  const [isSubmittingReturn, setIsSubmittingReturn] = useState(false);

  const addToast = (message, type = 'info') => {
    const id = Date.now() + Math.random();
    setToasts((prev) => [...prev, { id, message, type }]);
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4000);
  };

  const removeToast = (id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  // Check auth session on load
  const checkAuth = async () => {
    try {
      setCheckingAuth(true);
      const res = await authApi.me();
      if (res.data.authenticated) {
        setUser(res.data.user);
        fetchCounts();
      } else {
        setUser(null);
      }
    } catch (err) {
      setUser(null);
    } finally {
      setCheckingAuth(false);
    }
  };

  const fetchCounts = async () => {
    try {
      const res = await dashboardApi.getStats();
      const s = res.data.stats || {};
      setCounts({
        documents: s.total || 0,
        borrowings: s.borrowed || 0,
        overdue: s.overdue || 0,
      });
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    checkAuth();
  }, []);

  const handleLogout = async () => {
    try {
      await authApi.logout();
      setUser(null);
      addToast('Anda telah berhasil keluar dari sistem.', 'info');
    } catch (err) {
      console.error(err);
    }
  };

  const navigate = (page, docId = null) => {
    setCurrentPage(page);
    setSelectedDocId(docId);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    fetchCounts();
  };

  // Handle return modal confirm
  const handleReturnConfirm = async (formData) => {
    if (!returnModalData) return;
    setIsSubmittingReturn(true);
    try {
      const res = await borrowingApi.returnDoc(returnModalData.id, formData);
      addToast(res.data.message || 'Dokumen berhasil dikembalikan.', 'success');
      setReturnModalData(null);
      fetchCounts();
      // Reload current view if on details or borrowings
      if (currentPage === 'document-detail') {
        setSelectedDocId((prev) => prev); // trigger
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal mengembalikan berkas.';
      addToast(msg, 'error');
    } finally {
      setIsSubmittingReturn(false);
    }
  };

  if (checkingAuth) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-sky-500 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-xs font-bold text-slate-400 tracking-wider uppercase">
            Memuat Sistem Informasi SIP-PB WH...
          </p>
        </div>
      </div>
    );
  }

  if (!user) {
    return (
      <>
        <LoginPage
          onLoginSuccess={(userData) => {
            setUser(userData);
            fetchCounts();
          }}
          onToast={addToast}
        />
        <ToastContainer toasts={toasts} onDismiss={removeToast} />
      </>
    );
  }

  const renderPage = () => {
    switch (currentPage) {
      case 'dashboard':
        return (
          <DashboardPage
            onNavigate={navigate}
            onToast={addToast}
            onOpenReturnModal={(id, nomor, borrower) => setReturnModalData({ id, nomor_pb: nomor, nama_peminjam: borrower })}
          />
        );
      case 'documents':
        return (
          <DocumentsPage
            onNavigate={navigate}
            onToast={addToast}
          />
        );
      case 'document-create':
        return (
          <DocumentFormPage
            onNavigate={navigate}
            onToast={addToast}
          />
        );
      case 'document-edit':
        return (
          <DocumentFormPage
            documentId={selectedDocId}
            onNavigate={navigate}
            onToast={addToast}
          />
        );
      case 'document-detail':
        return (
          <DocumentDetailPage
            documentId={selectedDocId}
            onNavigate={navigate}
            onToast={addToast}
            onOpenPreview={setPreviewFile}
            onOpenReturnModal={(id, nomor, borrower) => setReturnModalData({ id, nomor_pb: nomor, nama_peminjam: borrower })}
            onOpenBorrowModal={setBorrowModalDoc}
          />
        );
      case 'racks':
        return (
          <WarehouseRacksPage
            onNavigate={navigate}
            onToast={addToast}
          />
        );
      case 'borrowings':
        return (
          <BorrowingsPage
            onNavigate={navigate}
            onToast={addToast}
            onOpenReturnModal={(id, nomor, borrower) => setReturnModalData({ id, nomor_pb: nomor, nama_peminjam: borrower })}
            onOpenBorrowModal={(doc) => setBorrowModalDoc(doc || {})}
          />
        );
      case 'qrcodes':
        return (
          <QrCodePrintPage
            onNavigate={navigate}
            onToast={addToast}
          />
        );
      case 'activity-logs':
        return (
          <AuditTrailPage
            onToast={addToast}
          />
        );
      default:
        return (
          <DashboardPage
            onNavigate={navigate}
            onToast={addToast}
          />
        );
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 flex text-slate-900 font-sans">
      {/* Sidebar */}
      <Sidebar
        currentPage={currentPage}
        navigate={navigate}
        user={user}
        onLogout={handleLogout}
        counts={counts}
        isOpen={mobileMenuOpen}
        onClose={() => setMobileMenuOpen(false)}
      />

      {/* Main Wrapper */}
      <div className="flex-1 lg:pl-72 flex flex-col min-h-screen w-full">
        <Navbar
          onOpenMobileMenu={() => setMobileMenuOpen(true)}
          currentPage={currentPage}
          user={user}
          onNavigate={navigate}
        />

        <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
          {renderPage()}
        </main>
      </div>

      {/* Global Toast Alerts */}
      <ToastContainer toasts={toasts} onDismiss={removeToast} />

      {/* Modals */}
      <QuickPreviewModal file={previewFile} onClose={() => setPreviewFile(null)} />

      <ReturnModal
        data={returnModalData}
        onClose={() => setReturnModalData(null)}
        onSubmit={handleReturnConfirm}
        isSubmitting={isSubmittingReturn}
      />

      {borrowModalDoc && (
        <BorrowModal
          document={borrowModalDoc.id ? borrowModalDoc : null}
          onClose={() => setBorrowModalDoc(null)}
          onSuccess={() => {
            fetchCounts();
            if (currentPage === 'document-detail') {
              setSelectedDocId((prev) => prev);
            }
          }}
          onToast={addToast}
        />
      )}
    </div>
  );
}
