<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }} - Rezerviši stan širom Srbije</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="{{ __('frontpage.hero.seo_description') }}">
    <meta name="image" content="{{ asset('images/default-image.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="images/favicon.ico">

    <meta property="og:locale" content="sr">
    <meta property="og:site_name" content="Aparto">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }} - Rezerviši stan širom Srbije">
    <meta property="og:description" content="{{ __('frontpage.hero.seo_description') }}">
    <meta property="og:image" content="{{ asset('images/default-image.png') }}">

    @stack('head')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="aparto-body">
    <main class="aparto-shell">
        @yield('content')
    </main>
    @include('layouts.partials.footer')

</body>
</html>
