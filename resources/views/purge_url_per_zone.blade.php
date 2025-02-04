@extends('statamic::layout')
@section('title', __('Cloudflare Manager'))

@section('content')
    <header class="mb-3">
        @include('statamic::partials.breadcrumb', [
            'url' => cp_route('utilities.index'),
            'title' => __('Utilities')
        ])
    </header>

    @include('statamic-cloudflare-addon::partials.cloudflare-navigation', ['activeTab' => 'purgeUrlPerZone'])

    @php
        use BrewDigital\StatamicCloudflareAddon\Cloudflare;

        $isConfigured = Cloudflare::isConfigured();
    @endphp

    @if ($isConfigured)
    <publish-form
            title="Purge URL Per Zone"
            :blueprint='@json($blueprint["blueprint"])'
            :meta='@json($blueprint["meta"])'
            :values='@json($blueprint["values"])'
            action="{{ cp_route('utilities.cloudflare.purgeUrlPerZone') }}"
    ></publish-form>
    @else
        <div class="py-6">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                <p class="font-bold">{{ __('Notice') }}</p>
                <p>{{ __('The Cloudflare addon is not enabled or configured for the current environment. Some features may not be available.') }}</p>
            </div>
        </div>
    @endif
@endsection