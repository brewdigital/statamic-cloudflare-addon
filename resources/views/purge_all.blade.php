@extends('statamic::layout')
@section('title', __('Cloudflare Manager'))

@section('content')
    <header class="mb-3">
        @include('statamic::partials.breadcrumb', [
            'url' => cp_route('utilities.index'),
            'title' => __('Utilities')
        ])
    </header>

    @include('statamic-cloudflare-addon::partials.cloudflare-navigation', ['activeTab' => 'purgeAll'])

    @php
        use BrewDigital\StatamicCloudflareAddon\Cloudflare;

        $isConfigured = Cloudflare::isConfigured();
    @endphp

    @if ($isConfigured)
    <div class="py-6">
        <h2 class="text-xl font-bold mb-4">{{ __('Purge Selected Zone') }}</h2>
        <form method="POST" action="{{ cp_route('utilities.cloudflare.purgeAllPerZone') }}">
            @csrf
            <div class="mb-4">
                <label for="selected_zone" class="block text-gray-700 text-sm font-bold mb-2">{{ __('Select Zone to Purge') }}</label>
                <select id="selected_zone" name="selected_zone" class="input-text">
                    @foreach($zones as $zoneId => $domain)
                        <option value="{{ $zoneId }}" {{ session('selected_zone') == $zoneId ? 'selected' : '' }}>
                            {{ $domain }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">
                {{ __('Purge Selected Zone') }}
            </button>
        </form>
    </div>
    <div>
        Or
    </div>
    <div class="py-6">
        <form method="POST" action="{{ cp_route('utilities.cloudflare.purgeAll') }}">
            @csrf
            <button type="submit" class="btn-primary">
                {{ __('Purge All Zones') }}
            </button>
        </form>
    </div>
    @else
        <div class="py-6">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                <p class="font-bold">{{ __('Notice') }}</p>
                <p>{{ __('The Cloudflare addon is not enabled or configured for the current environment. Some features may not be available.') }}</p>
            </div>
        </div>
    @endif
@endsection