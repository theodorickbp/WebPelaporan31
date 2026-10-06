<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2F5BEA">
    <meta name="description" content="@yield('meta-description', 'SI-PASAR - lapor kerusakan sarana kelas (jaringan, mouse/keyboard, listrik, proyektor) dengan foto bukti, dan pantau perbaikannya sampai selesai.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SI-PASAR') - SI-PASAR</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sipasar.min.css') }}?v={{ @filemtime(public_path('css/sipasar.min.css')) }}">
</head>
<body class="@yield('body-class')">
    <a class="skip-link" href="#main">Lewati ke konten</a>
    @yield('page')
    <script src="{{ asset('js/app.min.js') }}?v={{ @filemtime(public_path('js/app.min.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
