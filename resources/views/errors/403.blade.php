@extends('layouts.app')
@section('title', 'Access Restricted')
@section('content')
<section class="card"><span class="eyebrow">403 · ACCESS RESTRICTED</span><h1>You do not have access to this page.</h1><p class="muted">Contact your administrator if you need additional access.</p><a class="button" href="{{ route('dashboard') }}">Back to dashboard</a></section>
@endsection
