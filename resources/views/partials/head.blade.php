<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#e1000c">
<title>@yield('title', 'Dashboard') · ShopAds</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/shopads-icon.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/shopads-touch-icon.png') }}">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=space-grotesk:500,700|plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/brands.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-portal.css') }}?v={{ filemtime(public_path('css/auth-portal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
@stack('styles')
