<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – SIP-PB Warehouse PT Besmindo</title>
    <meta name="description" content="Login Sistem Informasi Pengarsipan Dokumen PB PT Besmindo Materi Sewatama">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

@if(session('toast_info'))
<div class="toast-stack" id="toastStack">
    <div class="toast-item toast-info">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('toast_info') }}</span>
    </div>
</div>
@endif

<div class="login-screen">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-container">
                <img src="{{ asset('images/besmindo-logo.jpg') }}" alt="PT Besmindo Materi Sewatama"
                     class="login-logo-img"
                     onerror="this.style.display='none'">
            </div>
            <div class="login-brand-badge">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Warehouse Archive System
            </div>
            <h1 class="login-title">Sistem Pengarsipan &amp; Pencarian PB</h1>
            <p class="login-company">PT BESMINDO MATERI SEWATAMA</p>
            <p class="login-subtitle">Masukkan username dan password untuk akses sistem</p>
        </div>

        {{-- Quick Fill Buttons --}}
        <div class="quick-fill-box">
            <span class="quick-fill-label">Pilihan Akses Cepat:</span>
            <div class="quick-fill-grid">
                <button type="button" class="btn-quick-fill" onclick="fillAdmin()">
                    <strong>Admin Warehouse</strong>
                    <span>Akses Penuh / Supervisor</span>
                </button>
                <button type="button" class="btn-quick-fill" onclick="fillPetugas()">
                    <strong>Staff / Pengguna Umum</strong>
                    <span>Akses Semua Karyawan</span>
                </button>
            </div>
        </div>

        {{-- Error Messages --}}
        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px;color:#b91c1c;font-size:0.82rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="username">Username / ID Pegawai</label>
                <div class="form-input-icon-wrap">
                    <svg class="form-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <input id="username" type="text" name="username" class="form-input"
                           value="{{ old('username', 'admin') }}"
                           placeholder="Masukkan username atau nama Anda..." required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <div class="form-input-icon-wrap">
                    <svg class="form-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input id="password" type="password" name="password" class="form-input"
                           value="admin123"
                           placeholder="Masukkan password..." required>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;font-size:0.8rem;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--text-muted);">
                    <input type="checkbox" name="remember" checked>
                    Ingat sesi saya
                </label>
            </div>

            <button type="submit" class="btn-login-submit">
                <span>Masuk ke Sistem Warehouse</span>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="login-footer-info">
            &copy; 2026 PT Besmindo Materi Sewatama &bull; Oilfield Warehouse Division
        </div>
    </div>
</div>

<script>
function fillAdmin()   { document.getElementById('username').value='admin';   document.getElementById('password').value='admin123'; }
function fillPetugas() { document.getElementById('username').value='petugas'; document.getElementById('password').value='petugas123'; }
setTimeout(() => { const s = document.getElementById('toastStack'); if (s) { s.style.opacity='0'; setTimeout(()=>s.remove(),400); } }, 3500);
</script>
</body>
</html>
