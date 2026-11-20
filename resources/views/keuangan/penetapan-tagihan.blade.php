<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penetapan Tagihan UKT</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f6f8;
            color: #202b3c;
            font-family: Arial, Helvetica, sans-serif;
        }

        .topbar {
            background: #172b4d;
            color: white;
            padding: 18px 6%;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            display: grid;
            place-items: center;
            background: #ffffff;
            color: #172b4d;
            font-weight: bold;
        }

        .brand-title {
            font-size: 16px;
            font-weight: bold;
        }

        .brand-subtitle {
            font-size: 12px;
            color: #cbd5e1;
            margin-top: 4px;
        }

        .container {
            max-width: 1250px;
            margin: 36px auto;
            padding: 0 24px;
        }

        .breadcrumb {
            font-size: 13px;
            color: #79869a;
            margin-bottom: 18px;
        }

        .heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        h1 {
            font-size: 26px;
            margin: 0 0 9px;
        }

        .description {
            color: #738096;
            font-size: 14px;
            line-height: 1.5;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: white;
            border: 1px solid #e6e9ef;
            border-radius: 10px;
            padding: 20px;
        }

        .summary-label {
            font-size: 13px;
            color: #748094;
        }

        .summary-number {
            font-size: 25px;
            font-weight: bold;
            margin-top: 10px;
        }

        .panel {
            background: white;
            border: 1px solid #e5e8ee;
            border-radius: 10px;
            overflow: hidden;
        }

        .panel-header {
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .panel-header h2 {
            font-size: 17px;
            margin: 0 0 6px;
        }

        .panel-header p {
            margin: 0;
            color: #7a8597;
            font-size: 13px;
        }

        .filter-form label {
            display: block;
            font-size: 12px;
            margin-bottom: 7px;
            color: #69768a;
        }

        select,
        input {
            width: 100%;
            border: 1px solid #d8dee8;
            background: white;
            border-radius: 6px;
            padding: 10px 11px;
            color: #263449;
            font-size: 13px;
        }

        select:focus,
        input:focus {
            outline: none;
            border-color: #4266a5;
            box-shadow: 0 0 0 2px #4266a51a;
        }

        .filter-form select {
            min-width: 190px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th {
            background: #f8f9fb;
            color: #6b778c;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            padding: 15px 18px;
        }

        td {
            border-top: 1px solid #edf0f4;
            padding: 17px 18px;
            font-size: 13px;
            vertical-align: middle;
        }

        .student-name {
            font-weight: 600;
            color: #25344a;
        }

        .student-nim {
            font-size: 12px;
            color: #7b8798;
            margin-top: 5px;
        }

        .row-form {
            display: grid;
            grid-template-columns: 140px 150px 125px;
            align-items: center;
            gap: 8px;
        }

        .btn {
            border: none;
            padding: 10px 12px;
            border-radius: 6px;
            background: #244b83;
            color: white;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .btn:hover {
            background: #193965;
        }

        .status {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            border-radius: 20px;
            padding: 6px 10px;
        }

        .pending {
            background: #fff4dc;
            color: #956514;
        }

        .paid {
            background: #e2f5e9;
            color: #207744;
        }

        .empty {
            padding: 40px;
            text-align: center;
            color: #7c8799;
        }

        .alert {
            padding: 14px 17px;
            margin-bottom: 18px;
            border-radius: 7px;
            font-size: 13px;
        }

        .alert-success {
            background: #e7f6ec;
            color: #216c3a;
            border: 1px solid #c5e8d0;
        }

        .alert-error {
            background: #fff0ef;
            color: #a33d35;
            border: 1px solid #f3cfcb;
        }

        .footer-note {
            font-size: 12px;
            color: #8490a0;
            margin-top: 17px;
            line-height: 1.5;
        }

        @media (max-width: 700px) {
            .container {
                margin-top: 24px;
                padding: 0 14px;
            }

            .heading,
            .panel-header {
                flex-direction: column;
            }

            .summary {
                grid-template-columns: 1fr;
            }

            .filter-form,
            .filter-form select {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header class="topbar">
        <div class="brand">UKT</div>
        <div>
            <div class="brand-title">Sistem Informasi Pembayaran Kuliah</div>
            <div class="brand-subtitle">Panel Administrasi Keuangan</div>
        </div>
    </header>

    <main class="container">

        <div class="breadcrumb">
            Dashboard / Keuangan / Penetapan Tagihan
        </div>

        <section class="heading">
            <div>
                <h1>Penetapan Tagihan UKT</h1>
                <div class="description">
                    Kelola dan tetapkan besaran tagihan UKT mahasiswa
                    berdasarkan periode semester.
                </div>
            </div>
        </section>

        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="alert alert-error">
            <strong>Penetapan belum berhasil.</strong>
            <ul style="margin-bottom: 0;">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php
        $totalMahasiswa = $mahasiswa->count();
        $totalDitetapkan = $tagihanByNim->count();
        $belumDitetapkan = max(0, $totalMahasiswa - $totalDitetapkan);
        @endphp

        <section class="summary">
            <div class="summary-card">
                <div class="summary-label">Total Mahasiswa Terdaftar</div>
                <div class="summary-number">{{ $totalMahasiswa }}</div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Tagihan Telah Ditetapkan</div>
                <div class="summary-number">{{ $totalDitetapkan }}</div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Belum Ditetapkan</div>
                <div class="summary-number">{{ $belumDitetapkan }}</div>
            </div>
        </section>

        <section class="panel">

            <div class="panel-header">
                <div>
                    <h2>Daftar Mahasiswa</h2>
                    <p>
                        Pilih periode semester dan tentukan nominal UKT
                        setiap mahasiswa.
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('keuangan.penetapan-tagihan.index') }}"
                    class="filter-form">
                    <label for="semester">Periode Semester</label>

                    <select
                        name="semester"
                        id="semester"
                        onchange="this.form.submit()">
                        @foreach ($semesterOptions as $option)
                        <option
                            value="{{ $option }}"
                            {{ $semester === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Nominal UKT / Status</th>
                            <th>Batas Pembayaran</th>
                            <th>Pengaturan Tagihan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($mahasiswa as $item)

                        @php
                        $existingBill = $tagihanByNim->get($item->nim);
                        @endphp

                        <tr>
                            <td>
                                <div class="student-name">
                                    {{ $item->nama }}
                                </div>
                                <div class="student-nim">
                                    NIM: {{ $item->nim }}
                                </div>
                            </td>

                            <td>
                                @if ($existingBill)
                                <strong>
                                    Rp {{ number_format((float) $existingBill->nominal, 0, ',', '.') }}
                                </strong>

                                <div style="margin-top: 8px;">
                                    <span class="status {{ $existingBill->status_bayar === 'Lunas' ? 'paid' : 'pending' }}">
                                        {{ $existingBill->status_bayar }}
                                    </span>
                                </div>
                                @else
                                <span style="color:#9aa3b0;">
                                    Belum ditetapkan
                                </span>
                                @endif
                            </td>

                            <td>
                                @if ($existingBill)
                                {{ $existingBill->batas_waktu->format('d-m-Y') }}
                                @else
                                <span style="color:#9aa3b0;">-</span>
                                @endif
                            </td>

                            <td>
                                @if ($existingBill)

                                <span style="font-size:12px;color:#738096;">
                                    Tagihan sudah tersimpan.
                                </span>

                                @else

                                <form
                                    class="row-form"
                                    method="POST"
                                    action="{{ route('keuangan.penetapan-tagihan.store') }}">
                                    @csrf

                                    <input
                                        type="hidden"
                                        name="nim"
                                        value="{{ $item->nim }}">

                                    <input
                                        type="hidden"
                                        name="semester"
                                        value="{{ $semester }}">

                                    <input
                                        type="number"
                                        name="nominal"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="Nominal (Rp)"
                                        value="{{ old('nim') === $item->nim ? old('nominal') : '' }}"
                                        required>

                                    <input
                                        type="date"
                                        name="batas_waktu"
                                        min="{{ now()->format('Y-m-d') }}"
                                        value="{{ old('nim') === $item->nim ? old('batas_waktu') : '' }}"
                                        required>

                                    <button class="btn" type="submit">
                                        Simpan Penetapan
                                    </button>
                                </form>

                                @endif
                            </td>
                        </tr>

                        @empty

                        <tr>
                            <td colspan="4" class="empty">
                                Belum terdapat data mahasiswa.
                            </td>
                        </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="footer-note">
            Informasi: Tagihan yang berhasil disimpan akan tersedia
            pada halaman tagihan mahasiswa dengan status awal
            Belum Dibayar.
        </div>

    </main>

</body>

</html>