@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')
<main class="auth-shell" id="main-content">
    <section class="auth-frame" aria-label="Sign in to Message Space">
        <div class="auth-hero">
            <div class="auth-hero-inner">
                <div>
                    <a class="brand-lockup" href="{{ route('login') }}"><div class="brand-mark"><img src="{{ asset('assets/message-space.svg') }}" alt="" class="brand-logo"></div><p class="brand-subtitle">Message Space</p></a>
                    <div class="hero-copy">
                        <p class="eyebrow">A SPACE TO CONNECT</p>
                        <h1 class="auth-title">The right message.<br>Stronger connections.</h1>
                        <p class="auth-description">Manage WhatsApp templates and campaigns in one organized workspace. From your first idea to a message ready to send.</p>
                        <div class="auth-message" aria-hidden="true">
                            <div class="auth-message__label"><span class="status-dot"></span> MESSAGE PREVIEW</div>
                            <div class="auth-message__bubble"><strong>Hello, welcome 👋</strong><p>Every conversation is the start of a great connection. Make every message count.</p></div>
                            <span class="auth-message__foot">Designed for better conversations</span>
                        </div>
                    </div>
                </div>
                <div class="hero-footer"><p>One space. Every conversation.</p><span>&copy; {{ date('Y') }} Message Space</span></div>
            </div>
        </div>
        <div class="auth-panel">
            <div class="panel-card">
                <h2 class="panel-title">Sign in to Message Space</h2>
                <p class="panel-copy">Enter your username and password to continue.</p>
                @include('partials.notices')
                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    <div class="field-group"><label for="username" class="field-label">Username</label><input class="text-input" id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Enter your username" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="50" required autofocus @error('username') aria-invalid="true" @enderror></div>
                    <div class="field-group"><label for="password" class="field-label">Password</label><input class="text-input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror></div>
                    <div class="field-actions"><label class="checkbox-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Remember me</span></label></div>
                    <button class="submit-btn" type="submit">Sign in</button>
                    <p class="register-prompt">Need an account? Contact your administrator to get started.</p>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
