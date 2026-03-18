@extends('layouts.app')

@push('head')
    <link rel="canonical" href="{{ route('pages.show', ['locale' => app()->getLocale(), 'slug' => $pageSlug]) }}">
    @foreach($availableLocales as $locale)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ route('pages.show', ['locale' => $locale, 'slug' => $pageSlug]) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ route('pages.show', ['locale' => 'sr', 'slug' => $pageSlug]) }}">
@endpush

@section('content')

    @include('layouts.partials.header')

    <section class="aparto-detail">
        <div class="aparto-detail-card">
            <h1 class="aparto-detail-title">{{ $page->title }}</h1>
            <div class="aparto-detail-description">
                {!! $page->content !!}
            </div>
        </div>
    </section>

    {{-- @include('frontend.partials.info') --}}
    
@endsection
