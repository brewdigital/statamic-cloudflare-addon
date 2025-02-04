<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait API
{
    /**
     * @return \Cloudflare\API\Endpoints\API
     */
    public function API()
    {
        return new \Cloudflare\API\Endpoints\API($this->instance);
    }
}
