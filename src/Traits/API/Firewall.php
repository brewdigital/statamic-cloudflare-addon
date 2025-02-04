<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait Firewall
{
    /**
     * @return \Cloudflare\API\Endpoints\Firewall
     */
    public function Firewall()
    {
        return new \Cloudflare\API\Endpoints\Firewall($this->instance);
    }
}
