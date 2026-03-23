@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; text-align:center; padding:2rem;">
        <span style="font-size:10rem; font-weight:800; line-height:1; color:#374151">404</span>
        <h2 style="margin-top:1rem; font-size:1.5rem; font-weight:600; color:#374151;">{{ __('frontpage.404.title') }}</h2>
        <p style="margin-top:0.5rem; color:#6b7280;">{{ __('frontpage.404.message') }}</p>
        <a href="{{ route('home') }}"
           style="display:inline-block; margin-top:2rem; padding:0.75rem 2rem; background:#2563eb; color:#fff; border-radius:0.5rem; text-decoration:none; font-weight:600; transition:background 0.2s;"
           onmouseover="this.style.background='#1d4ed8'"
           onmouseout="this.style.background='#2563eb'">
            {{ __('frontpage.404.back_to_home') }}
        </a>
    </div>
@endsection
