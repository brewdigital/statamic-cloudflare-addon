<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait ZoneLockdown
{
    /**
     * @return \Cloudflare\API\Endpoints\void
     */
    public function ZoneLockdown()
    {
        return new \Cloudflare\API\Endpoints\ZoneLockdown($this->instance);
    }
}
