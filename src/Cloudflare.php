<?php

namespace BrewDigital\StatamicCloudflareAddon;

use BrewDigital\StatamicCloudflareAddon\Traits\RegistersCacher;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Modifiers\Modify;
use Statamic\Sites\Site;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;

class Cloudflare
{
    use RegistersCacher;

    protected static $booted;
    protected static $isConfigured;

    protected static $api;
    protected static $zones;
    protected static $zoneId;

    public static function boot()
    {
        $currentEnvironment = app()->environment();
        $config = static::loadConfig();

        if (!$config) {
            throw new \Exception('Configuration file not found or is invalid.', 1);
        }

        $enabled = false;
        switch ($currentEnvironment) {
            case 'local':
                $enabled = $config['enable_in_local'] ?? false;
                break;
            case 'staging':
                $enabled = $config['enable_in_staging'] ?? false;
                break;
            case 'production':
                $enabled = $config['enable_in_production'] ?? false;
                break;
        }

        // Set isConfigured to false if not enabled for the current environment
        if (!$enabled) {
            static::$isConfigured = false;
            return;
        }

        if (static::isNotConfigured()) {
            throw new \Exception('No Api connection has been configured for statamic-cloudflare-addon.', 1);
        }

        if (!static::$booted) {
            static::$booted = true;

            static::$api = new CloudflareApi();
            static::$zones = collect(static::config('zones', []))->pluck('zone_id', 'domain');
        }
    }

    public static function isConfigured(): bool
    {
        if (is_null(static::$isConfigured)) {
            $currentEnvironment = app()->environment();
            $config = static::loadConfig();

            if (!$config) {
                static::$isConfigured = false;
            } else {
                $enabled = false;
                switch ($currentEnvironment) {
                    case 'local':
                        $enabled = $config['enable_in_local'] ?? false;
                        break;
                    case 'staging':
                        $enabled = $config['enable_in_staging'] ?? false;
                        break;
                    case 'production':
                        $enabled = $config['enable_in_production'] ?? false;
                        break;
                }

                static::$isConfigured = $enabled && (static::config('email') && static::config('key') || static::config('token'));
            }
        }

        return static::$isConfigured;
    }

    public static function isNotConfigured(): bool
    {
        return !static::isConfigured();
    }

    public static function config($key = null, $default = null)
    {
        $config = static::loadConfig();

        if (!$config) {
            return $default;
        }

        if (is_null($key)) {
            return $config;
        }

        return $config[$key] ?? $default;
    }

    public static function shouldQueue()
    {
        return static::config('queued') ? true : false;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function zones()
    {
        static::boot();

        return static::$zones;
    }

    /**
     * @return \BrewDigital\StatamicCloudflareAddon\CloudflareApi
     */
    public static function Api()
    {
        static::boot();

        return static::$api;
    }

    public static function getZoneIdForSite(Site $site)
    {
        static::boot();

        $siteUrl = Modify::value($site->absoluteUrl())
            ->removeLeft('http://')
            ->removeLeft('https://')
            ->removeRight('/')
            ->explode(':')
            ->first()
            ->__toString();

        return static::$zones->get($siteUrl) ?? static::Api()->Zones()->getZoneID($siteUrl);
    }

    public static function getZoneIdForCurrentSite()
    {
        static::boot();

        return static::getZoneIdForSite(SiteFacade::current());
    }

    /**
     * Load the YAML configuration file from the resources/config directory.
     *
     * @return array|null
     */
    public static function loadConfig(): ?array
    {
        $path = resource_path('config/statamic-cloudflare.yaml');

        if (!File::exists($path)) {
            return null;
        }

        try {
            return Yaml::parseFile($path);
        } catch (\Exception $e) {
            return null;
        }
    }
}