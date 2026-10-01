<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head')</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten</a>
    <div class="portal-shell">
        <button type="button" class="portal-overlay" data-menu-close aria-label="Tutup menu" tabindex="-1"></button>
        <aside class="portal-sidebar" id="mobile-menu" aria-label="Menu utama">
            <div class="portal-sidebar__inner">
                <div class="portal-sidebar__mobile-head">
                    <p class="portal-sidebar__mobile-title">Menu</p>
                    <button type="button" class="portal-sidebar__close" data-menu-close aria-label="Tutup menu"><span></span><span></span></button>
                </div>
                <a class="portal-brand" href="{{ route('dashboard') }}">
                    <img src="{{ asset('assets/shopads-icon.svg') }}" alt="" class="portal-brand__logo">
                    <span class="portal-brand__text">ShopAds</span>
                </a>
                @auth
                    <a class="portal-create-btn portal-create-btn--link" href="{{ auth()->user()->isAdmin() ? route('admin.users.index') : route('user.whatsapp.broadcasts.create') }}">{{ auth()->user()->isAdmin() ? 'Kelola Pengguna' : '+ Buat Broadcast' }}</a>
                @endauth
                <nav class="portal-nav" aria-label="Navigasi utama">
                    <div class="portal-nav__item {{ request()->routeIs('*.dashboard') ? 'portal-nav__item--active' : '' }}">
                        <a class="portal-nav__head portal-nav__link" href="{{ route('dashboard') }}" @if(request()->routeIs('*.dashboard')) aria-current="page" @endif>
                            @include('partials.portal-sidebar-icon', ['icon' => 'dashboard'])
                            <span>Dashboard</span>
                        </a>
                    </div>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <div class="portal-nav__item {{ request()->routeIs('admin.users.*') ? 'portal-nav__item--active' : '' }}">
                                <a class="portal-nav__head portal-nav__link" href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                                    <span class="portal-nav__icon-wrap"><svg class="portal-nav__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 4v2"/></svg></span>
                                    <span>Pengguna</span>
                                </a>
                            </div>
                            <div class="portal-nav__item {{ request()->routeIs('admin.whatsapp.templates.*') ? 'portal-nav__item--active' : '' }}">
                                <a class="portal-nav__head portal-nav__link" href="{{ route('admin.whatsapp.templates.index') }}" @if(request()->routeIs('admin.whatsapp.templates.*')) aria-current="page" @endif>
                                    @include('partials.portal-sidebar-icon', ['icon' => 'wa'])<span>Persetujuan Template</span>
                                </a>
                            </div>
                        @else
                            @include('user.whatsapp.partials.navigation')
                        @endif
                    @endauth
                </nav>
                @auth
                    <div class="sidebar-account"><span class="status-dot"></span>{{ auth()->user()->isAdmin() ? 'Administrator' : 'Akun user' }}<small>Kelola akun dan akses Anda.</small></div>
                    <form class="sidebar-logout" action="{{ route('logout') }}" method="POST">@csrf<button class="button secondary" type="submit">Keluar dari akun</button></form>
                @endauth
            </div>
        </aside>
        <div class="portal-main">
            <header class="portal-topbar">
                <div class="portal-topbar__left">
                    <button type="button" class="portal-burger" data-menu-toggle aria-label="Buka menu" aria-controls="mobile-menu" aria-expanded="false"><span></span><span></span><span></span></button>
                    <div class="portal-topbar__spacer">ShopAds<span class="portal-topbar__context"> / @yield('title', 'Dashboard')</span></div>
                </div>
                @auth
                    <div class="portal-topbar__actions">
                        <div class="portal-user"><span class="portal-user__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="portal-user__name">{{ auth()->user()->name }}</span></div>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="portal-logout">Keluar</button></form>
                    </div>
                @endauth
            </header>
            <main id="main-content" class="portal-content">
                @include('partials.notices')
                @yield('content')
            </main>
            <footer class="app-footer"><span>&copy; {{ date('Y') }} ShopAds</span><span>Portal pengelolaan akun</span></footer>
        </div>
    </div>
    <script src="{{ asset('js/portal-navigation.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
