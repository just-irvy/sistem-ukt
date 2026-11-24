@php
    $portalRole = auth()->user()?->role;
    $portalDashboard = match ($portalRole) {
        \App\Models\Akun::ROLE_MAHASISWA => 'dashboard.mahasiswa',
        \App\Models\Akun::ROLE_VERIFIKATOR => 'dashboard.verifikator',
        \App\Models\Akun::ROLE_KEUANGAN => 'dashboard.keuangan',
        default => 'login',
    };
    $portalLinks = match ($portalRole) {
        \App\Models\Akun::ROLE_MAHASISWA => [
            ['route' => 'dashboard.mahasiswa', 'label' => 'Beranda'],
            ['route' => 'tagihan.index', 'label' => 'Tagihan'],
            ['route' => 'pengajuan.create', 'label' => 'Penyesuaian'],
            ['route' => 'mahasiswa.tagihan.index', 'label' => 'Pembayaran'],
        ],
        \App\Models\Akun::ROLE_VERIFIKATOR => [
            ['route' => 'dashboard.verifikator', 'label' => 'Beranda'],
            ['route' => 'verifikasi.index', 'label' => 'Verifikasi'],
        ],
        \App\Models\Akun::ROLE_KEUANGAN => [
            ['route' => 'dashboard.keuangan', 'label' => 'Beranda'],
            ['route' => 'keuangan.penetapan-tagihan.index', 'label' => 'Penetapan UKT'],
            ['route' => 'keuangan.riwayat-rekonsiliasi.index', 'label' => 'Rekonsiliasi'],
        ],
        default => [],
    };
@endphp

<a class="skip-link" href="#konten-utama">Langsung ke konten</a>
<header class="topbar" id="halaman-atas">
    <div class="site-header-inner">
        <a class="brand-link" href="{{ route($portalDashboard) }}" aria-label="Sistem UKT - Beranda">
            <span class="brand" aria-hidden="true">UKT</span>
            <span class="brand-copy">
                <span class="brand-title">Sistem Informasi Pembayaran UKT</span>
                <span class="brand-subtitle">Portal akademik <span class="brand-separator" aria-hidden="true">/</span> {{ $section ?? 'Layanan UKT' }}</span>
            </span>
        </a>

        @auth
            <nav class="primary-nav" aria-label="Navigasi utama">
                @foreach ($portalLinks as $link)
                    <a
                        class="{{ request()->routeIs($link['route']) ? 'is-active' : '' }}"
                        href="{{ route($link['route']) }}"
                        @if(request()->routeIs($link['route'])) aria-current="page" @endif
                    >{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="header-account">
                <div class="account-details">
                    <span class="account-label">{{ $portalRole }}</span>
                    <span class="account-name">{{ auth()->user()->username }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-keluar" type="submit">Keluar</button>
                </form>
            </div>
        @else
            <nav class="guest-nav" aria-label="Navigasi akun">
                <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Masuk</a>
                <a class="guest-register" href="{{ route('registrasi') }}" @if(request()->routeIs('registrasi')) aria-current="page" @endif>Daftar Akun</a>
            </nav>
        @endauth
    </div>
</header>
