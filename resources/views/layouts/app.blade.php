@extends('layouts.base')

@section('body-class', 'app')

@section('page')
    @php
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $unread = $user->unreadNotifications()->count();
        $pendingAccounts = $isAdmin ? \App\Models\User::pending()->count() : 0;

        $nav = $isAdmin
            ? [
                ['route' => 'admin.home', 'match' => ['admin.home'], 'label' => 'Ringkasan', 'icon' => 'layout'],
                ['route' => 'admin.reports.index', 'match' => ['admin.reports.*'], 'label' => 'Laporan', 'icon' => 'inbox'],
                ['route' => 'admin.accounts.index', 'match' => ['admin.accounts.*'], 'label' => 'Akun', 'icon' => 'users', 'badge' => $pendingAccounts],
                ['route' => 'notifications.index', 'match' => ['notifications.*'], 'label' => 'Notifikasi', 'icon' => 'bell', 'badge' => $unread],
                ['route' => 'profile.edit', 'match' => ['profile.*'], 'label' => 'Profil', 'icon' => 'user'],
            ]
            : [
                ['route' => 'student.home', 'match' => ['student.home'], 'label' => 'Beranda', 'icon' => 'home'],
                ['route' => 'student.reports.index', 'match' => ['student.reports.index', 'student.reports.show'], 'label' => 'Riwayat', 'icon' => 'list'],
                ['route' => 'student.reports.create', 'match' => ['student.reports.create'], 'label' => 'Lapor', 'icon' => 'camera', 'primary' => true],
                ['route' => 'notifications.index', 'match' => ['notifications.*'], 'label' => 'Notifikasi', 'icon' => 'bell', 'badge' => $unread],
                ['route' => 'profile.edit', 'match' => ['profile.*'], 'label' => 'Profil', 'icon' => 'user'],
            ];
    @endphp

    <div class="shell">
        {{-- Sidebar (layar lebar) --}}
        <aside class="rail">
            <x-brand :href="route('dashboard')" />

            @unless ($isAdmin)
                <a href="{{ route('student.reports.create') }}" class="btn btn-sun btn-block rail-cta">
                    <x-icon name="camera" :size="20" /> Buat laporan
                </a>
            @endunless

            <nav class="rail-nav" aria-label="Menu utama">
                @foreach ($nav as $item)
                    @continue($item['primary'] ?? false)
                    @php $active = request()->routeIs(...$item['match']); @endphp
                    <a href="{{ route($item['route']) }}" @class(['rail-link', 'is-active' => $active]) @if ($active) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" :size="20" />
                        <span>{{ $item['label'] }}</span>
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="badge badge-inline">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="rail-user">
                <div class="rail-user-name">{{ $user->name }}</div>
                <div class="rail-user-sub">{{ $user->roleSubtitle() }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-block"><x-icon name="logout" :size="18" /> Keluar</button>
                </form>
            </div>
        </aside>

        {{-- Bilah atas (HP) --}}
        <header class="topbar">
            <x-brand :href="route('dashboard')" />
            <a href="{{ route('notifications.index') }}" class="topbar-bell" aria-label="Notifikasi{{ $unread > 0 ? ', '.$unread.' belum dibaca' : '' }}">
                <x-icon name="bell" :size="22" />
                @if ($unread > 0)
                    <span class="badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>
        </header>

        <main id="main" class="main">
            <div id="pwa-banner" class="pwa-banner" hidden>
                <p class="pwa-banner-text" data-text-prompt hidden>Pasang SI-PASAR di HP-mu biar lebih cepat dibuka, seperti aplikasi biasa.</p>
                <p class="pwa-banner-text" data-text-ios hidden>Pasang SI-PASAR: ketuk tombol Bagikan di Safari, lalu pilih &ldquo;Tambah ke Layar Utama&rdquo;.</p>
                <div class="pwa-banner-actions">
                    <button type="button" class="btn btn-sun" data-pwa-install>Pasang</button>
                    <button type="button" class="pwa-banner-close" data-pwa-close aria-label="Tutup"><x-icon name="x" :size="18" /></button>
                </div>
            </div>
            @if (session('toast'))
                <div class="toast" role="status" data-toast>{{ session('toast') }}</div>
            @endif
            @yield('content')
        </main>

        {{-- Tab bawah (HP) --}}
        <nav class="tabbar" aria-label="Menu utama">
            @foreach ($nav as $item)
                @php $active = request()->routeIs(...$item['match']); @endphp
                <a href="{{ route($item['route']) }}" @class(['tab', 'is-active' => $active, 'tab-primary' => $item['primary'] ?? false]) @if ($active) aria-current="page" @endif>
                    <span class="tab-icon">
                        <x-icon :name="$item['icon']" :size="22" />
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="badge">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                        @endif
                    </span>
                    <span class="tab-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @stack('zoom-viewer')
@endsection
