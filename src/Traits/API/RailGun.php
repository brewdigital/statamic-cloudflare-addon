<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait RailGun
{
    /**
     * @return \Cloudflare\API\Endpoints\Railgun
     */
    public function Railgun()
    {
        return new \Cloudflare\API\Endpoints\Railgun($this->instance);
    }
}
