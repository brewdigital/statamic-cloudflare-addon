<?php

namespace BrewDigital\StatamicCloudflareAddon\Traits\API;

trait AccessRules
{
    /**
     * @return AccessRules
     */
    public function AccessRules()
    {
        return new \Cloudflare\API\Endpoints\AccessRules($this->instance);
    }
}
