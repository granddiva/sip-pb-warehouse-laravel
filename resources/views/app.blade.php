<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIP-PB WH – PT Besmindo Materi Sewatama</title>
    <meta name="description" content="Sistem Informasi Pengarsipan dan Pelacakan Dokumen Permintaan Barang (PB) Warehouse PT Besmindo Materi Sewatama">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script>
        window.csrfToken = "{{ csrf_token() }}";
        window.appName = "SIP-PB WH";
        window.companyName = "PT Besmindo Materi Sewatama";
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/main.jsx'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    <div id="root"></div>
</body>
</html>
