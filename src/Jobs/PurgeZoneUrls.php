<?php

namespace BrewDigital\StatamicCloudflareAddon\Jobs;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\Modifiers\Modify;

class PurgeZoneUrls implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $zone;
    protected $urls;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $zone, array $urls)
    {
        $this->zone = $zone;
        $this->urls = $urls;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $domain = Cloudflare::zones()->flip()->get($this->zone);
            $domain = Modify::value($domain)
                ->removeLeft('www.')
                ->__toString();

            $urls = [];

            foreach ($this->urls as $url) {
                $urls[] = "http://{$domain}{$url}";
                $urls[] = "https://{$domain}{$url}";

                $urls[] = "http://www.{$domain}{$url}";
                $urls[] = "https://www.{$domain}{$url}";
            }
            Cloudflare::Api()->Zones()->cachePurge($this->zone, $urls);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return null;
    }

    public function getZone(): string
    {
        return $this->zone;
    }
}
