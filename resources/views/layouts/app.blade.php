<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul') - Sistem UKT</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f6f8; }
        header { background: #1f3a5f; color: #fff; padding: 12px 24px; display: flex; justify-content: space-between; }
        main { max-width: 960px; margin: 24px auto; padding: 0 16px; }
        .kartu { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; }
        label { display: block; margin-bottom: 6px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px 16px; background: #1f3a5f; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        .btn-lebar { width: 100%; }
        .pesan-error, .pesan-error-utama { color: #b00020; }
        .pesan-sukses { color: #1b7f3b; margin-bottom: 12px; }
    </style>
</head>
<body>
    <header>
        <strong>Sistem Informasi Pembayaran UKT</strong>
        <span>@yield('peran')</span>
    </header>
    <main>
        @yield('konten')
    </main>
</body>
</html>