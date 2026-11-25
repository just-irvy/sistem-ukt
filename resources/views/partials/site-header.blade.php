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
            <span class="brand" aria-hidden="true">
                <svg class="brand-mark" width="32" height="32" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 15.5 24 8l17 7.5L24 23 7 15.5Z" fill="#204F82"/>
                    <path d="M14 20.1V25c0 3.5 5.1 6.6 10 6.6s10-3.1 10-6.6v-4.9" fill="#A5C1DD" stroke="#204F82" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M41 15.6v10.3" stroke="#204F82" stroke-width="2.2" stroke-linecap="round"/>
                    <circle cx="41" cy="28" r="2.8" fill="#D7A94C"/>
                    <path d="M12 31v5.5c0 1.4 1.1 2.5 2.5 2.5h17" stroke="#204F82" stroke-width="2.2" stroke-linecap="round"/>
                    <path d="M18 35h8" stroke="#204F82" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="34.5" cy="35.5" r="7" fill="#F8E8BE" stroke="#204F82" stroke-width="2"/>
                    <path d="M31.8 35.5h5.4M34.5 32.7v5.6" stroke="#204F82" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </span>
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
