<?php

namespace BrewDigital\StatamicCloudflareAddon\Jobs;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeEverythingForZone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $zone;

    /**
     * Create a new job instance.
     *
     * @param string $zone
     * @param bool $dispatchedSync
     * @return void
     */
    public function __construct(string $zone)
    {
        $this->zone = $zone;
    }

    /**
     * Execute the job.
     *
     * @return null|string
     */
    public function handle(): null|string
    {
        Cloudflare::Api()->Zones()->cachePurgeEverything($this->zone);
        return null;
    }

    public function getZone(): string
    {
        return $this->zone;
    }
}