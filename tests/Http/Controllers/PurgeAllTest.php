<?php

namespace BrewDigital\StatamicCloudflareAddon\Tests\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Tests\TestCase;
use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Illuminate\Support\Collection;
use Mockery;
class PurgeAllTest extends TestCase
{
    public function testPurgeAllWhenCloudflareNotConfigured()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);

        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAll'), []);
        $response->assertRedirect();
        $response->assertSessionHas('error', 'The Cloudflare addon is not enabled or configured for this environment.');
    }

    public function testPurgeAllWhenNoZoneSelected()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);
        $cloudflareMock->shouldReceive('zones')
            ->once()
            ->andReturn(new Collection([]));
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAll'), []);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Zones are not configured.');
    }

    public function testPurgeAllSuccess()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);
        $cloudflareMock->shouldReceive('shouldQueue')
            ->once()
            ->andReturn(false);

        $cloudflareMock->shouldReceive('zones')
            ->twice()
            ->andReturn(collect(['Zone 1' => 'zone1', 'Zone 2' => 'zone2']));

        $apiMock = Mockery::mock();
        $cloudflareMock->shouldReceive('Api')
            ->twice()
            ->andReturn($apiMock);

        $zonesMock = Mockery::mock();
        $apiMock->shouldReceive('Zones')
            ->twice()
            ->andReturn($zonesMock);

        $zonesMock->shouldReceive('cachePurgeEverything')
            ->with('zone1')
            ->once()
            ->andReturnNull();

        $zonesMock->shouldReceive('cachePurgeEverything')
            ->with('zone2')
            ->once()
            ->andReturnNull();

        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAll'));
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Purged all successfully.');
    }
}