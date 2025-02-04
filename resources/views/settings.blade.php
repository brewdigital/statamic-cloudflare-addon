@extends('statamic::layout')
@section('title', __('Cloudflare Manager'))

@section('content')
    <header class="mb-3">
        @include('statamic::partials.breadcrumb', [
            'url' => cp_route('utilities.index'),
            'title' => __('Utilities')
        ])
    </header>

    @include('statamic-cloudflare-addon::partials.cloudflare-navigation', ['activeTab' => 'settings'])

    <publish-form
            title="Settings"
            :blueprint='@json($blueprint["blueprint"])'
            :meta='@json($blueprint["meta"])'
            :values='@json($blueprint["values"])'
            action="{{ cp_route('utilities.cloudflare.settings') }}"
    ></publish-form>
@endsection