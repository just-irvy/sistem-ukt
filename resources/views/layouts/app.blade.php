<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul') | Sistem Informasi UKT</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f6f8; color: #202b3c; font-family: Arial, Helvetica, sans-serif; }

        /* Topbar: gaya sama dengan halaman Penetapan Tagihan (UI-06) */
        .topbar { background: #172b4d; color: #fff; padding: 18px 6%; display: flex; align-items: center; gap: 14px; }
        .brand { width: 40px; height: 40px; border-radius: 9px; display: grid; place-items: center; background: #fff; color: #172b4d; font-weight: bold; }
        .brand-title { font-size: 16px; font-weight: bold; }
        .brand-subtitle { font-size: 12px; color: #cbd5e1; margin-top: 4px; }
        .topbar-kanan { margin-left: auto; display: flex; align-items: center; gap: 14px; font-size: 13px; }
        .topbar-kanan form { margin: 0; }
        .topbar-kanan a { color: #cbd5e1; text-decoration: none; }
        .topbar-kanan a:hover { color: #fff; }
        .btn-keluar { background: transparent; border: 1px solid #5b6f94; color: #fff; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .btn-keluar:hover { background: #244b83; }

        .container { max-width: 1250px; margin: 36px auto; padding: 0 24px; }
        .breadcrumb { font-size: 13px; color: #79869a; margin-bottom: 8px; }
        h1 { margin: 0 0 6px; font-size: 28px; color: #172b4d; }
        h2 { margin: 0 0 6px; font-size: 18px; color: #172b4d; }
        .subjudul { color: #738096; margin: 0 0 24px; }
        .ket { color: #738096; font-size: 13px; margin: 0 0 16px; }

        .kartu { background: #fff; border: 1px solid #e3e7ee; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        .tata-letak { display: block; }
        .grid-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        @media (max-width: 720px) { .grid-form { grid-template-columns: 1fr; } .topbar { padding: 14px 4%; } }
        .grid-form .penuh { grid-column: 1 / -1; }

        label { display: block; font-size: 13px; font-weight: bold; color: #38465c; margin-bottom: 6px; }
        input[type=text], input[type=password], input[type=number], input[type=file], select, textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #cfd6e2; border-radius: 8px; background: #fff; color: #263449; font: inherit; }
        textarea { min-height: 110px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #4266a5; }

        .btn { display: inline-block; padding: 10px 18px; background: #244b83; color: #fff; border: 0; border-radius: 8px; cursor: pointer; font: inherit; font-weight: bold; text-decoration: none; }
        .btn:hover { background: #193965; }
        .btn-abu { background: #e8ecf3; color: #38465c; }
        .btn-abu:hover { background: #d9e0ec; }
        .btn-lebar { width: 100%; text-align: center; }
        .btn-merah { background: #b3392f; }
        .btn-merah:hover { background: #8f2a22; }
        .btn-hijau { background: #207744; }
        .btn-hijau:hover { background: #185c35; }
        .tautan { color: #244b83; text-decoration: none; font-size: 14px; }
        .tautan:hover { text-decoration: underline; }

        .pesan-sukses { background: #e7f6ec; color: #216c3a; border: 1px solid #b9e2c6; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
        .pesan-error-utama { background: #fdeaea; color: #9b2c2c; border: 1px solid #f1c0c0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
        .pesan-error { color: #b3392f; font-size: 12px; margin: 6px 0 0; }

        /* Tabel & status (dipakai UI-04 dan UI-05) */
        .tabel-bungkus { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; background: #f8f9fb; color: #6b778c; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; padding: 12px; border-bottom: 1px solid #e3e7ee; }
        td { padding: 14px 12px; border-bottom: 1px solid #eef1f6; vertical-align: top; }
        .kosong { text-align: center; color: #7c8799; padding: 28px 12px; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .status-menunggu { background: #fff4dc; color: #956514; }
        .status-setuju { background: #e2f5e9; color: #207744; }
        .status-tolak { background: #fdeaea; color: #9b2c2c; }
        .detail { display: grid; grid-template-columns: 180px 1fr; gap: 10px 16px; font-size: 14px; }
        .detail dt { color: #738096; }
        .detail dd { margin: 0; }
        .aksi { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">U</div>
        <div>
            <div class="brand-title">Sistem Informasi Pembayaran UKT</div>
            <div class="brand-subtitle">@yield('peran', 'Universitas')</div>
        </div>
        @auth
            <div class="topbar-kanan">
                <a href="{{ match (auth()->user()->role) {
                    \App\Models\Akun::ROLE_MAHASISWA => route('dashboard.mahasiswa'),
                    \App\Models\Akun::ROLE_KEUANGAN => route('dashboard.keuangan'),
                    default => route('dashboard.verifikator'),
                } }}">Dashboard</a>
                <span>{{ auth()->user()->username }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-keluar">Keluar</button>
                </form>
            </div>
        @endauth
    </header>

    <main class="container">
        @yield('konten')
    </main>
</body>
</html>
