<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait User
{
    /**
     * @return \Cloudflare\API\Endpoints\User
     */
    public function User()
    {
        return new \Cloudflare\API\Endpoints\User($this->instance);
    }
}
