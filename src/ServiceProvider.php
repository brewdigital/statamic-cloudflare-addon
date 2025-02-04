<?php

namespace BrewDigital\StatamicCloudflareAddon;

use BrewDigital\StatamicCloudflareAddon\Http\Controllers\CloudflareUtilityController;
use Statamic\Facades\Permission;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;
use Illuminate\Support\Facades\Route;
use Edalzell\Forma\Forma;
use Statamic\Facades\CP\Nav;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        \BrewDigital\StatamicCloudflareAddon\Commands\CachePurgeEverything::class,
    ];

    protected $listen = [
        \Statamic\Events\EntrySaved::class => [
            \BrewDigital\StatamicCloudflareAddon\Listeners\PurgeEntryUrl::class,
        ],
        \Statamic\Events\EntryDeleted::class => [
            \BrewDigital\StatamicCloudflareAddon\Listeners\PurgeEntryUrl::class,
        ],
    ];

    public function bootAddon()
    {
        $static_cacher = config('statamic.static_caching.strategy');

        if (config("statamic.static_caching.strategies.{$static_cacher}.driver") === 'cloudflare') {
            $this->listen = [];
        }

        if (Cloudflare::isNotConfigured()) {
            $this->listen = [];
        }

        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/config/statamic-cloudflare.yaml' => resource_path('config/statamic-cloudflare.yaml')
            ], 'config');
        }
    }

    public function register(): void
    {
        parent::register();

        Permission::register('manage brew cloudflare')
            ->label('Manage Brew Cloudflare Settings');

        Utility::extend(function () {
            $utility = Utility::register('cloudflare')
                ->title(__('Cloudflare Manager'))
                ->navTitle(__('Cloudflare'))
                ->description('Purge Cloudflare from the comfort of your Statamic CP.')
                ->icon('earth')
                ->action([CloudflareUtilityController::class, 'showPurgeAll']);

            $utility->routes(function ($router) {
                // GET routes for rendering views
                $router->get('/purgeAll', [CloudflareUtilityController::class, 'showPurgeAll'])->name('cloudflare.purgeAll');
                $router->get('/purgeCollectionsPerZone', [CloudflareUtilityController::class, 'showPurgeCollectionsPerZone'])->name('cloudflare.purgeCollectionsPerZone');
                $router->get('/purgeUrlPerZone', [CloudflareUtilityController::class, 'showPurgeUrlPerZone'])->name('cloudflare.purgeUrlPerZone');
                $router->get('/settings', [CloudflareUtilityController::class, 'showSettings'])->name('cloudflare.settings');

                // POST routes for handling form submissions
                $router->post('/purgeAllPerZone', [CloudflareUtilityController::class, 'purgeAllPerZone'])->name('purgeAllPerZone');
                $router->post('/purgeAll', [CloudflareUtilityController::class, 'purgeAll'])->name('purgeAll');
                $router->post('/purgeCollectionsPerZone', [CloudflareUtilityController::class, 'purgeCollectionsPerZone'])->name('purgeCollectionsPerZone');
                $router->post('/purgeUrlPerZone', [CloudflareUtilityController::class, 'purgeUrlPerZone'])->name('purgeUrlPerZone');
                $router->post('/settings', [CloudflareUtilityController::class, 'updateSettings'])->name('settings');
            });

        });

        Nav::extend(function ($nav) {
            $nav->content('Brew Cloudflare')
                ->route('utilities.cloudflare.purgeAll')
                ->section('Settings')
                ->can('manage brew cloudflare')
                ->icon('settings-horizontal');
        });
    }
}