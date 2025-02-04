<?php

namespace BrewDigital\StatamicCloudflareAddon\Listeners;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeEverythingForAllZones;
use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeZoneUrls;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\TermSaved;
use Statamic\Events\TermDeleted;
use Statamic\Modifiers\Modify;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;


class PurgeEntryUrl
{
    public function handle($event)
    {
        if (
            !$event instanceof EntrySaved &&
            !$event instanceof EntryDeleted &&
            !$event instanceof TermSaved &&
            !$event instanceof TermDeleted ) {
            return;
        }

        if (!Cloudflare::isConfigured()) {
            return;
        }

        $currentEnvironment = app()->environment();
        $config = $this->loadConfig();
        $purgeEverythingOnEntrySave = $config['purge_everything_on_entry_save'];

        if ($purgeEverythingOnEntrySave) {
            $zones = Cloudflare::zones();

            if ($zones->isEmpty()) {
                return;
            }

            $zones->each(function ($zoneId, $zoneName) {
                try {
                    if (Cloudflare::shouldQueue()) {
                        PurgeEverythingForAllZones::dispatch();
                    } else {
                        PurgeEverythingForAllZones::dispatchSync();
                    }
                }catch (\Exception $e) {
                    throw new \Exception("Entry saved but cloudflare purge failed");
                }
            });

            return;
        } else {
            $site = $event->entry->site();
            $domain = Modify::value($site->absoluteUrl())
                ->removeLeft('http://')
                ->removeLeft('https://')
                ->removeLeft('www.')
                ->__toString();

            $zone = Cloudflare::zones()->get($domain);

            if (!$zone) {
                $zone = Cloudflare::zones()->get("www.{$domain}");
            }

            if ($zone) {
                $url = $event->entry->url();
                try {
                    if (Cloudflare::shouldQueue()) {
                        PurgeZoneUrls::dispatch($zone, [$url]);
                    } else {
                        PurgeZoneUrls::dispatchSync($zone, [$url]);
                    }
                } catch (\Exception $e) {
                    throw new \Exception("Entry saved but cloudflare purge failed");
                }
            }
        }
    }

    protected static function loadConfig()
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