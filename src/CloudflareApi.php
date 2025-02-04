<?php

namespace BrewDigital\StatamicCloudflareAddon;

use Cloudflare\API\Adapter\Guzzle as CloudflareClient;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Auth\APIToken;
use BrewDigital\StatamicCloudflareAddon\Traits\API\AccessRules;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Accounts;
use BrewDigital\StatamicCloudflareAddon\Traits\API\API;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Certicates;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Crypto;
use BrewDigital\StatamicCloudflareAddon\Traits\API\CustomHostnames;
use BrewDigital\StatamicCloudflareAddon\Traits\API\DNS;
use BrewDigital\StatamicCloudflareAddon\Traits\API\DNSAnalytics;
use BrewDigital\StatamicCloudflareAddon\Traits\API\EndpointException;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Firewall;
use BrewDigital\StatamicCloudflareAddon\Traits\API\FirewallSettings;
use BrewDigital\StatamicCloudflareAddon\Traits\API\IPs;
use BrewDigital\StatamicCloudflareAddon\Traits\API\LoadBalancers;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Membership;
use BrewDigital\StatamicCloudflareAddon\Traits\API\PageRules;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Pools;
use BrewDigital\StatamicCloudflareAddon\Traits\API\RailGun;
use BrewDigital\StatamicCloudflareAddon\Traits\API\SSL;
use BrewDigital\StatamicCloudflareAddon\Traits\API\TLS;
use BrewDigital\StatamicCloudflareAddon\Traits\API\UARules;
use BrewDigital\StatamicCloudflareAddon\Traits\API\User;
use BrewDigital\StatamicCloudflareAddon\Traits\API\WAF;
use BrewDigital\StatamicCloudflareAddon\Traits\API\ZoneLockdown;
use BrewDigital\StatamicCloudflareAddon\Traits\API\Zones;
use BrewDigital\StatamicCloudflareAddon\Traits\API\ZoneSettings;

class CloudflareApi
{
    use AccessRules;
    use Accounts;
    use API;
    use Certicates;
    use Crypto;
    use CustomHostnames;
    use DNS;
    use DNSAnalytics;
    use EndpointException;
    use Firewall;
    use FirewallSettings;
    use IPs;
    use LoadBalancers;
    use Membership;
    use PageRules;
    use Pools;
    use RailGun;
    use SSL;
    use TLS;
    use UARules;
    use User;
    use WAF;
    use ZoneLockdown;
    use Zones;
    use ZoneSettings;

    protected $instance;

    public function __construct()
    {
        if (Cloudflare::config('key')) {
            $API = new APIKey(Cloudflare::config('email'), Cloudflare::config('key'));
        } else {
            $API = new APIToken(Cloudflare::config('token'));
        }

        $this->instance = new CloudflareClient($API);
    }

    /**
     * @return \Cloudflare\API\Endpoints\CloudflareClient
     */
    public function instance()
    {
        return $this->instance();
    }
}
