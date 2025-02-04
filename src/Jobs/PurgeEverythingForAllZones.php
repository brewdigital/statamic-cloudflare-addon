<?php

namespace BrewDigital\StatamicCloudflareAddon\Jobs;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeEverythingForAllZones implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param bool $dispatchedSync
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        Cloudflare::zones()->each(function ($zoneId, $zoneName) {
            Cloudflare::Api()->Zones()->cachePurgeEverything($zoneId);
        });

        return null;
    }
}