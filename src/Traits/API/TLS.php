<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait TLS
{
    /**
     * @return \Cloudflare\API\Endpoints\TLS
     */
    public function TLS()
    {
        return new \Cloudflare\API\Endpoints\TLS($this->instance);
    }
}
