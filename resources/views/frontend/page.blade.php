@extends('layouts.app')

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
    
@endsection
