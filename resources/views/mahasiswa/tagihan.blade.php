<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan Semester - Sistem Pembayaran UKT</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f6f8; color: #202b3c; }
        .topbar { background: #172b4d; color: #fff; padding: 18px 6%; display: flex; align-items: center; gap: 14px; }
        .brand { width: 40px; height: 40px; border-radius: 9px; display: grid; place-items: center; background: #fff; color: #172b4d; font-weight: bold; }
        .brand-title { font-size: 16px; font-weight: bold; }
        .brand-subtitle { font-size: 12px; color: #cbd5e1; margin-top: 4px; }
        .container { max-width: 1080px; margin: 36px auto; padding: 0 24px; }
        .breadcrumb { font-size: 13px; color: #79869a; margin-bottom: 18px; }
        h1 { margin: 0 0 9px; font-size: 26px; }
        .description { color: #738096; font-size: 14px; line-height: 1.6; margin: 0 0 25px; }
        .panel { background: #fff; border: 1px solid #e5e8ee; border-radius: 10px; padding: 22px; margin-bottom: 18px; }
        .panel-title { margin: 0 0 16px; font-size: 17px; }
        .filters { display: grid; grid-template-columns: 1fr 1fr auto; align-items: end; gap: 14px; }
        label { display: block; margin-bottom: 7px; font-size: 13px; color: #69768a; }
        input, select { width: 100%; border: 1px solid #d8dee8; background: #fff; border-radius: 6px; padding: 11px; color: #263449; font-size: 14px; }
        .btn { border: 0; border-radius: 6px; padding: 12px 17px; background: #244b83; color: #fff; font-size: 13px; font-weight: bold; cursor: pointer; }
        .btn:disabled { background: #9aa6b5; cursor: not-allowed; }
        .student { margin-bottom: 18px; font-size: 14px; }
        .student strong { color: #243751; }
        .details { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .detail { background: #f8f9fb; border-radius: 8px; padding: 17px; }
        .detail-label { color: #748094; font-size: 12px; margin-bottom: 9px; }
        .detail-value { font-size: 19px; font-weight: bold; overflow-wrap: anywhere; }
        .status { display: inline-block; font-size: 12px; padding: 6px 11px; border-radius: 20px; }
        .pending { background: #fff4dc; color: #956514; }
        .paid { background: #e2f5e9; color: #207744; }
        .notice { margin-top: 18px; border: 1px solid #dbe5f2; border-radius: 8px; padding: 15px; color: #53657c; background: #f4f8fe; font-size: 13px; line-height: 1.6; }
        .alert { border: 1px solid #f3cfcb; color: #a33d35; background: #fff0ef; padding: 14px; border-radius: 7px; margin-bottom: 18px; font-size: 13px; }
        .alert-success { border-color: #bce5c6; color: #205d32; background: #edf9ef; }
        .alert-info { border-color: #dbe5f2; color: #53657c; background: #f4f8fe; }
        .empty { padding: 24px; text-align: center; color: #7b8798; font-size: 14px; }
        .muted { color: #738096; font-size: 13px; line-height: 1.6; }
        .va { margin-top: 18px; padding-top: 18px; border-top: 1px solid #edf0f4; }
        .va-number { margin: 8px 0; font-size: 20px; font-weight: bold; letter-spacing: 1px; overflow-wrap: anywhere; }
        @media (max-width: 700px) {
            .container { margin: 24px auto; padding: 0 14px; }
            .filters, .details { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/ukt-refinement.css') }}">
</head>
<body class="mahasiswa-page">
<header class="topbar">
    <div class="brand">UKT</div>
    <div>
        <div class="brand-title">Sistem Informasi Pembayaran Kuliah</div>
        <div class="brand-subtitle">Pembayaran UKT · Mahasiswa</div>
    </div>
    <nav class="topbar-links" aria-label="Navigasi mahasiswa">
        <a href="{{ route('dashboard.mahasiswa') }}">Kembali ke Dashboard</a>
    </nav>
</header>

<main class="container">
    <div class="breadcrumb"><a href="{{ route('dashboard.mahasiswa') }}" style="color: inherit;">Dashboard</a> / Mahasiswa / Pembayaran Virtual Account</div>
    <h1>Tagihan Semester</h1>
    <p class="description">Lihat tagihan dan nomor Virtual Account BCA Sandbox milik akun mahasiswa yang sedang login.</p>

    <section class="panel">
        <h2 class="panel-title">Tagihan Mahasiswa</h2>
        <p class="muted">Tagihan ditampilkan untuk akun mahasiswa yang sedang login. NIM tidak dapat diganti melalui formulir.</p>

        @if ($errors->any())
            <div class="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="filters" method="GET" action="{{ route('mahasiswa.tagihan.index') }}">
            <div>
                <label>NIM Mahasiswa</label>
                <div class="readonly-field">{{ $mahasiswa->nim }}</div>
            </div>
            <div>
                <label for="semester">Periode Semester</label>
                <select id="semester" name="semester">
                    @if ($semester !== null && !$semesterOptions->contains($semester))
                        <option value="{{ $semester }}" selected>{{ $semester }} (periode pengujian)</option>
                    @endif
                    @forelse ($semesterOptions as $option)
                        <option value="{{ $option }}" @selected($semester === $option)>{{ $option }}</option>
                    @empty
                        <option value="">Belum ada semester</option>
                    @endforelse
                </select>
            </div>
            <button type="submit" class="btn">Tampilkan Tagihan</button>
        </form>
    </section>

    @if (session('va_success'))
        <div class="alert alert-success">{{ session('va_success') }}</div>
    @endif
    @if (session('va_info'))
        <div class="alert alert-info">{{ session('va_info') }}</div>
    @endif
    @if (session('va_error'))
        <div class="alert">{{ session('va_error') }}</div>
    @endif

    @if ($nim !== null)
        <section class="panel">
            @if ($mahasiswa === null)
                <div class="empty">Data mahasiswa dengan NIM tersebut tidak ditemukan.</div>
            @else
                <div class="student"><strong>{{ $mahasiswa->nama }}</strong> — NIM {{ $mahasiswa->nim }}</div>

                @if ($tagihan === null)
                    <div class="empty">Tagihan belum tersedia untuk semester yang dipilih.</div>
                @else
                    <h2 class="panel-title">Rincian Tagihan {{ $tagihan->semester }}</h2>
                    <div class="details">
                        <div class="detail">
                            <div class="detail-label">Nominal UKT</div>
                            <div class="detail-value">Rp {{ number_format((float) $tagihan->nominal, 0, ',', '.') }}</div>
                        </div>
                        <div class="detail">
                            <div class="detail-label">Batas Pembayaran Tagihan</div>
                            <div class="detail-value">{{ $tagihan->batas_waktu->format('d-m-Y') }}</div>
                        </div>
                        <div class="detail">
                            <div class="detail-label">Status Pembayaran</div>
                            <div class="status {{ $tagihan->status_bayar === 'Lunas' ? 'paid' : 'pending' }}">{{ $tagihan->status_bayar }}</div>
                        </div>
                    </div>

                    @if ($tagihan->status_bayar === 'Belum Dibayar')
                        <div class="va">
                            <h2 class="panel-title">Pembayaran Virtual Account</h2>
                            @if ($tagihan->virtual_account)
                                <div class="muted">Nomor VA yang tercatat (BCA Sandbox):</div>
                                <div class="va-number">{{ $tagihan->virtual_account }}</div>
                                @if ($tagihan->va_expires_at)
                                    @php
                                        // MidtransService menyimpan DATETIME sebagai waktu Asia/Jakarta (WIB).
                                        // Periksa masa berlaku lokal saja; ini bukan pemeriksaan status transaksi Midtrans.
                                        $vaExpiresAt = \Carbon\CarbonImmutable::parse($tagihan->va_expires_at, 'Asia/Jakarta');
                                        $vaTimePassed = $vaExpiresAt->lessThanOrEqualTo(\Carbon\CarbonImmutable::now('Asia/Jakarta'));
                                    @endphp
                                    <div class="muted">Batas berlaku VA (WIB): {{ $vaExpiresAt->format('d-m-Y H:i:s') }}</div>
                                    @if ($vaTimePassed)
                                        <div class="alert" style="margin-top: 12px; margin-bottom: 0;">
                                            Masa berlaku VA telah lewat berdasarkan waktu yang tersimpan.
                                            Jangan gunakan VA ini sebelum statusnya diperiksa di Midtrans Sandbox.
                                            Sistem tidak otomatis menghapus atau menerbitkan VA pengganti. Penerbitan VA pengganti hanya dilakukan setelah Midtrans mengonfirmasi status <strong>expire</strong>.
                                        </div>
                                        @if ($tagihan->midtrans_order_id)
                                            <form method="POST" action="{{ route('mahasiswa.tagihan.va.renew', ['idTagihan' => $tagihan->id_tagihan]) }}" style="margin-top: 12px;">
                                                @csrf
                                                <button type="submit" class="btn">Periksa Midtrans &amp; Minta VA Pengganti</button>
                                            </form>
                                            <p class="muted">Jika status lama masih pending/settlement atau tidak dapat diverifikasi, penerbitan VA baru ditolak.</p>
                                        @else
                                            <p class="muted">Order ID lama tidak tersedia. Hubungi Bagian Keuangan untuk pemeriksaan.</p>
                                        @endif
                                    @else
                                        <p class="muted">Masa berlaku belum terlewati berdasarkan waktu lokal yang tersimpan. Status transaksi sebenarnya tetap perlu diverifikasi di Midtrans Sandbox.</p>
                                    @endif
                                @elseif (session('va_expiry'))
                                    <div class="muted">Batas berlaku menurut respons Midtrans (sementara): {{ session('va_expiry') }}</div>
                                    <p class="muted">Waktu kedaluwarsa belum tersimpan di database. Verifikasi masa berlakunya di Midtrans Sandbox sebelum digunakan.</p>
                                @else
                                    <div class="muted">Masa berlaku VA ini belum tercatat di database (misalnya VA lama). Verifikasi status dan masa berlakunya di Midtrans Sandbox sebelum digunakan.</div>
                                @endif
                                <p class="muted">Gunakan simulator BCA Virtual Account di Midtrans Sandbox untuk pengujian; jangan mengirim pembayaran sungguhan.</p>
                            @else
                                <form method="POST" action="{{ route('mahasiswa.tagihan.va', ['idTagihan' => $tagihan->id_tagihan]) }}">
                                    @csrf
                                    <button type="submit" class="btn">Bayar via Virtual Account (BCA Sandbox)</button>
                                </form>
                                <p class="muted">Tombol akan mengirim permintaan VA ke Midtrans Sandbox. Tekan satu kali dan periksa hasilnya.</p>
                            @endif
                        </div>
                    @endif
                    <div class="notice">Nomor VA BCA dibuat hanya untuk tagihan akun mahasiswa yang sedang login melalui Midtrans Sandbox. Penerbitan VA tidak mengubah status tagihan menjadi Lunas; rekonsiliasi dilakukan terpisah (UC-08).</div>
                @endif
            @endif
        </section>
    @endif
</main>
</body>
</html>
