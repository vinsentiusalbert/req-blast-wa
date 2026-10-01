@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/whatsapp.css') }}?v={{ filemtime(public_path('css/whatsapp.css')) }}">
@endpush
