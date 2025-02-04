<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait PageRules
{
    /**
     * @return \Cloudflare\API\Endpoints\PageRules
     */
    public function PageRules()
    {
        return new \Cloudflare\API\Endpoints\PageRules($this->instance);
    }
}
