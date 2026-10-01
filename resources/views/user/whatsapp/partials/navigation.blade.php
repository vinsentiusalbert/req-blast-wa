<div class="portal-nav__item portal-nav__item--dropdown {{ request()->routeIs('user.whatsapp.*') ? 'portal-nav__item--active portal-nav__item--open' : '' }}" data-nav-group>
    <button type="button" class="portal-nav__head portal-nav__toggle" data-nav-toggle aria-controls="waba-submenu" aria-expanded="{{ request()->routeIs('user.whatsapp.*') ? 'true' : 'false' }}">
        @include('partials.portal-sidebar-icon', ['icon' => 'wa'])
        <span>WABA</span>
        <span class="portal-nav__caret" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
    </button>
    <div class="portal-subnav" id="waba-submenu" @if(!request()->routeIs('user.whatsapp.*')) inert @endif>
        <a class="portal-subnav__item {{ request()->routeIs('user.whatsapp.templates.*') ? 'portal-subnav__item--active' : '' }}" href="{{ route('user.whatsapp.templates.create') }}" @if(request()->routeIs('user.whatsapp.templates.*')) aria-current="page" @endif>
            <span class="portal-subnav__icon" aria-hidden="true">@include('partials.portal-sidebar-icon', ['icon' => 'template', 'subnav' => true])</span>
            <span>Buat Template</span>
        </a>
        <a class="portal-subnav__item {{ request()->routeIs('user.whatsapp.broadcasts.*') ? 'portal-subnav__item--active' : '' }}" href="{{ route('user.whatsapp.broadcasts.index') }}" @if(request()->routeIs('user.whatsapp.broadcasts.*')) aria-current="page" @endif>
            <span class="portal-subnav__icon" aria-hidden="true">@include('partials.portal-sidebar-icon', ['icon' => 'broadcast', 'subnav' => true])</span>
            <span>Broadcast WhatsApp</span>
        </a>
    </div>
</div>
