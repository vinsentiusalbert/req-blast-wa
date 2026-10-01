@extends('layouts.app')
@section('title', 'Akses Dibatasi')
@section('content')
<section class="card"><span class="eyebrow">403 · AKSES DIBATASI</span><h1>Halaman ini di luar akses Anda.</h1><p class="muted">Hubungi administrator jika Anda membutuhkan akses tambahan.</p><a class="button" href="{{ route('dashboard') }}">Kembali ke dashboard</a></section>
@endsection
