<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait DNSAnalytics
{
    /**
     * @return \Cloudflare\API\Endpoints\DNSAnalytics
     */
    public function DNSAnalytics()
    {
        return new \Cloudflare\API\Endpoints\DNSAnalytics($this->instance);
    }
}
