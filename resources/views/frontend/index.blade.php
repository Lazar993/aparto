@extends('layouts.app')

@section('content')

    @include('layouts.partials.header')
    @include('frontend.partials.main')
    @include('frontend.partials.search')
    @include('frontend.partials.apartments', ['apartments' => $apartments])
    @include('frontend.partials.info')
    
@endsection
