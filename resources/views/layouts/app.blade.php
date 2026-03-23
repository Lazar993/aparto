<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }} | @yield('seo_title', __('frontpage.hero.seo_title'))</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="@yield('seo_description', __('frontpage.hero.seo_description'))">
    <meta name="keywords" content="@yield('seo_keywords', __('frontpage.hero.seo_keywords'))">
    <meta name="image" content="@yield('seo_image', asset('images/default-image.png'))">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">

    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
    <meta property="og:site_name" content="Aparto">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }} | @yield('seo_title', __('frontpage.hero.seo_title'))">
    <meta property="og:description" content="@yield('seo_description', __('frontpage.hero.seo_description'))">
    <meta property="og:image" content="@yield('seo_image', asset('images/default-image.png'))">

    @php
        $currentPath = request()->path();
        $currentLocale = app()->getLocale();
        $allLocales = ['sr', 'en', 'ru'];
    @endphp
    <link rel="canonical" href="{{ url()->current() }}">
    @foreach($allLocales as $altLocale)
        <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ url(preg_replace('#^(' . implode('|', $allLocales) . ')(/|$)#', $altLocale . '$2', $currentPath)) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url(preg_replace('#^(' . implode('|', $allLocales) . ')(/|$)#', 'sr$2', $currentPath)) }}">

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
