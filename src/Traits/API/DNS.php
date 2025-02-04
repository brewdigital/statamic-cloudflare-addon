<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait DNS
{
    /**
     * @return \Cloudflare\API\Endpoints\DNS
     */
    public function DNS()
    {
        return new \Cloudflare\API\Endpoints\DNS($this->instance);
    }
}
