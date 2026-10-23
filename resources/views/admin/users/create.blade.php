@extends('layouts.app')
@section('title', 'Add User')
@section('content')
<a href="{{ route('admin.users.index') }}" class="back-link">&larr; Back to users</a>
<div class="page-heading"><div><span class="eyebrow">USER MANAGEMENT</span><h1>Add user</h1><p class="muted">Create a new user account. You can change its role from the user list.</p></div></div>
    <section class="card user-create-card" aria-label="New user details">
        <form method="POST" action="{{ route('admin.users.store') }}" class="login-form">
            @csrf
            <div class="field-group"><label class="field-label" for="name">Full name</label><input class="text-input" id="name" name="name" value="{{ old('name') }}" placeholder="Your full name" autocomplete="name" maxlength="255" required autofocus></div>
            <div class="field-group"><label class="field-label" for="username">Username</label><input class="text-input" id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Example: creative.team" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="50" aria-describedby="username-help" required><small class="field-hint" id="username-help">3–50 characters. Use letters, numbers, periods, underscores, or hyphens. Start with a letter or number.</small></div>
            <div class="field-group"><label class="field-label" for="email">Email address</label><input class="text-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" maxlength="255" required></div>
            <div class="field-group"><label class="field-label" for="password">Password</label><input class="text-input" id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" aria-describedby="password-help" required><small class="field-hint" id="password-help">At least 8 characters, including letters and numbers.</small></div>
            <div class="field-group"><label class="field-label" for="password_confirmation">Confirm password</label><input class="text-input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat password" autocomplete="new-password" minlength="8" required></div>
            <button class="submit-btn" type="submit">Create user account</button>
            <p class="register-prompt"><a href="{{ route('admin.users.index') }}">Cancel</a></p>
        </form>
    </section>
@endsection
