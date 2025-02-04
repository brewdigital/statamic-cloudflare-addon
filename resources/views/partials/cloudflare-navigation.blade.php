<!-- resources/views/partials/cloudflare-navigation.blade.php -->
<div class="flex border-b border-gray-300 dark:border-gray-700 mb-6 gap-2">
    <a href="{{ cp_route('utilities.cloudflare.purgeAll') }}" class="tab-button {{ $activeTab === 'purgeAll' ? 'active' : '' }}">
        {{ __('Purge Options') }}
    </a>
    <a href="{{ cp_route('utilities.cloudflare.purgeCollectionsPerZone') }}" class="tab-button {{ $activeTab === 'purgeCollectionsPerZone' ? 'active' : '' }}">
        {{ __('Purge Collections') }}
    </a>
    <a href="{{ cp_route('utilities.cloudflare.purgeUrlPerZone') }}" class="tab-button {{ $activeTab === 'purgeUrlPerZone' ? 'active' : '' }}">
        {{ __('Purge URL') }}
    </a>
    <a href="{{ cp_route('utilities.cloudflare.settings') }}" class="tab-button {{ $activeTab === 'settings' ? 'active' : '' }}">
        {{ __('Settings') }}
    </a>
</div>