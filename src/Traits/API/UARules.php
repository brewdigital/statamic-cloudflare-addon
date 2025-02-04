<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait UARules
{
    /**
     * @return \Cloudflare\API\Endpoints\UARules
     */
    public function UARules()
    {
        return new \Cloudflare\API\Endpoints\UARules($this->instance);
    }
}
