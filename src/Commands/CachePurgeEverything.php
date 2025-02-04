<?php

namespace BrewDigital\StatamicCloudflareAddon\Commands;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;

class CachePurgeEverything extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:cache:purge:everything';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge everything from CloudFlare.';

    public function handle()
    {
        $currentEnvironment = app()->environment();
        $config = $this->loadConfig();

        if (!$config) {
            return $this->error('Configuration file not found or is invalid.');
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

        if ($enabled) {
            if (Cloudflare::isNotConfigured()) {
                return $this->error('No Api connection has been configured for statamic-cloudflare.');
            }

            if (Cloudflare::zones()->isEmpty()) {
                return $this->error('Please supply a valid zone in the statamic-cloudflare config.');
            }

            Cloudflare::zones()->each(function ($zoneId, $zoneName) {
                try {
                    Cloudflare::Api()->Zones()->cachePurgeEverything($zoneId);

                    $this->info('Successfully purged everything from: '.Cloudflare::zones()->flip()->get($zoneName));
                } catch (\Exception $exception) {
                    $this->error('Failed to purge: '.Cloudflare::zones()->flip()->get($zone));
                }
            });
        }
    }

    /**
     * Load the YAML configuration file from the resources/config directory.
     *
     * @return array|null
     */
    protected function loadConfig()
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