<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIP-PB Warehouse') – PT Besmindo Materi Sewatama</title>
    <meta name="description" content="Sistem Informasi Pengarsipan dan Pencarian Dokumen Permintaan Barang (PB) PT Besmindo Materi Sewatama">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="app-layout">
    {{-- Sidebar --}}
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header" onclick="window.location='{{ route('dashboard') }}'" style="cursor:pointer;" title="Kembali ke Dashboard">
            <div class="sidebar-logo" style="overflow:hidden;padding:2px;background:#fff;display:flex;align-items:center;justify-content:center;">
                <img src="{{ asset('images/besmindo-logo.jpg') }}" alt="Logo BMS"
                     style="width:100%;height:100%;object-fit:contain;border-radius:4px;"
                     onerror="this.style.display='none'">
            </div>
            <div class="sidebar-brand">
                <h2>SIP-PB WH</h2>
                <p>PT BESMINDO MATERI SEWATAMA</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-category">Navigasi Utama</div>

            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Data Dokumen PB</span>
                <span class="nav-badge">{{ \App\Models\Document::count() }}</span>
            </a>

            <a href="{{ route('documents.create') }}" class="nav-link {{ request()->routeIs('documents.create') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span>Upload Dokumen PB</span>
            </a>

            <div class="nav-category">Manajemen Warehouse</div>

            <a href="{{ route('racks.index') }}" class="nav-link {{ request()->routeIs('racks.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Lokasi Rak & Box Arsip</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user-card">
                <div class="sidebar-user-avatar">{{ auth()->user()->avatar ?? 'US' }}</div>
                <div class="sidebar-user-info" style="overflow:hidden;">
                    <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                    <div class="sidebar-user-role">{{ auth()->user()->role }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-sidebar-logout" title="Keluar / Logout dari Sistem">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link" style="color:#f87171;width:100%;margin-top:8px;background:none;border:none;cursor:pointer;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar / Logout</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="main-wrapper">
        {{-- Navbar --}}
        <header class="top-navbar">
            <div class="navbar-left">
                <button class="btn-mobile-sidebar" id="mobileSidebarToggle" onclick="document.getElementById('appSidebar').classList.toggle('open')">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="navbar-breadcrumb">
                    <span class="breadcrumb-item">Warehouse</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="breadcrumb-item active">@yield('page_title', 'Dashboard')</span>
                </div>
            </div>
            <div class="navbar-right">
                <a href="{{ route('documents.create') }}" class="btn-navbar-upload">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Upload PB</span>
                </a>
                <div class="navbar-user-pill">
                    <div class="navbar-user-avatar">{{ auth()->user()->avatar ?? 'US' }}</div>
                    <span>{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <main class="content-container">
            {{-- Toast Notifications --}}
            @if(session('toast_success'))
                <div class="toast-stack" id="toastStack">
                    <div class="toast-item toast-success" id="toastMsg">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('toast_success') }}</span>
                    </div>
                </div>
            @endif
            @if(session('toast_warning'))
                <div class="toast-stack" id="toastStack">
                    <div class="toast-item toast-warning" id="toastMsg">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ session('toast_warning') }}</span>
                    </div>
                </div>
            @endif
            @if(session('toast_info'))
                <div class="toast-stack" id="toastStack">
                    <div class="toast-item toast-info" id="toastMsg">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('toast_info') }}</span>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
// Auto-dismiss toast
setTimeout(() => {
    const stack = document.getElementById('toastStack');
    if (stack) stack.style.opacity = '0';
    setTimeout(() => { if (stack) stack.remove(); }, 400);
}, 3500);
</script>
</body>
</html>
