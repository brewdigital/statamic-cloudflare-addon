<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait Membership
{
    /**
     * @return \Cloudflare\API\Endpoints\Membership
     */
    public function Membership()
    {
        return new \Cloudflare\API\Endpoints\Membership($this->instance);
    }
}
