@php
    $icon = $icon ?? 'dashboard';
@endphp

@if (empty($subnav))
    <span class="portal-nav__icon-wrap">
        @if ($icon === 'dashboard')
            <svg class="portal-nav__svg" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z" fill="currentColor"/>
            </svg>
        @elseif ($icon === 'sms')
            <svg class="portal-nav__svg" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7A2.5 2.5 0 0 1 17.5 16h-7.2L6 19.5V16.3A2.5 2.5 0 0 1 4 13.8z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M7.5 8.5h9M7.5 11.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        @elseif ($icon === 'wa')
            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
        @endif
    </span>
@else
    @if ($icon === 'location')
        <svg viewBox="0 0 24 24">
            <path d="M12 5a7 7 0 1 0 7 7c0-.8-.14-1.57-.4-2.28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M12 2v4M12 18v4M2 12h4M18 12h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="12" cy="12" r="2.5" fill="currentColor"/>
        </svg>
    @elseif ($icon === 'broadcast')
        <svg viewBox="0 0 24 24">
            <rect x="4" y="6" width="16" height="12" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
            <path d="M7 10h10M7 14h7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    @elseif ($icon === 'targeted')
        <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 3v4M12 17v4M3 12h4M17 12h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="12" cy="12" r="1.8" fill="currentColor"/>
        </svg>
    @elseif ($icon === 'template')
        <svg viewBox="0 0 24 24">
            <rect x="4" y="5" width="16" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/>
            <path d="M8 9h8M8 12h8M8 15h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    @endif
@endif
