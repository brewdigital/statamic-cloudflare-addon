<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait Pools
{
    /**
     * @return \Cloudflare\API\Endpoints\Pools
     */
    public function Pools()
    {
        return new \Cloudflare\API\Endpoints\Pools($this->instance);
    }
}
