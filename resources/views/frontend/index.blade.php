@extends('layouts.app')

@section('content')

    @include('layouts.partials.header')
    @include('frontend.partials.main')
    @include('frontend.partials.home-search')
    @include('frontend.partials.apartments', [
        'popularApartments' => $popularApartments,
        'bestRatedApartments' => $bestRatedApartments,
        'newestApartments' => $newestApartments,
    ])
    @include('frontend.partials.info')
    
@endsection
