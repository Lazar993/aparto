@extends('layouts.app')

@section('seo_title', $page->title)
@section('seo_description', Str::limit(strip_tags($page->content), 160))
@section('seo_keywords', $page->title . ', Aparto')

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
