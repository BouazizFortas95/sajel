<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#f59e0b">
<link rel="icon" href="/pwa-icon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/pwa-icon-192.png">
<meta name="csrf-token" content="{{ csrf_token() }}">
@vite('resources/js/app.js')
@include('partials.pwa-registration')
