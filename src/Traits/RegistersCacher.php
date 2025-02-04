<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits;

use Statamic\StaticCaching\Cachers\NullCacher;

trait RegistersCacher
{
    public static function registerCacher()
    {
        // Only register the cloudflare static cache drive, if the Api has been configured.
        // If it has not, then register NullCacher, so that the application doesn't have a tantrum,
        // if someone is using the cloudflare driver in their static cache.
        if (static::isConfigured()) {
            app(\Statamic\StaticCaching\StaticCacheManager::class)->extend('cloudflare', function ($app, $config, $name) {
                return new \BrewDigital\StatamicCloudflareAddon\Cachers\CloudflareCacher($app[\Illuminate\Cache\Repository::class], $config);
            });
        } else {
            app(\Statamic\StaticCaching\StaticCacheManager::class)->extend('cloudflare', function ($app, $config, $name) {
                return new NullCacher($app[\Illuminate\Cache\Repository::class], $config);
            });
        }
    }
}
