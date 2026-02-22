<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="{{ __('frontpage.hero.seo_description') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="aparto-body">
    <main class="aparto-shell">
        @yield('content')
    </main>
    @include('layouts.partials.footer')

    @if(app()->getLocale() === 'sr')
        <x-ai-chat />
    @endif

</body>
</html>
