<?php

namespace BrewDigital\StatamicCloudflareAddon\Tests\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Tests\TestCase;
use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Mockery;
use Illuminate\Support\Facades\Queue;
use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeEverythingForZone;
class PurgeAllPerZoneTest extends TestCase
{

    public function testCloudflareNotConfigured()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAllPerZone'), ['selected_zone' => 'zone1']);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'The Cloudflare addon is not enabled or configured for this environment.');
    }

    public function testZoneIdNotProvided()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);
        $this->asAdmin();
        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAllPerZone'));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Zones are not configured.');
    }

    public function testSuccessfulPurgeWithQueue()
    {
        Queue::fake();
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);
        $cloudflareMock->shouldReceive('shouldQueue')
            ->once()
            ->andReturn(true);
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAllPerZone'), ['selected_zone' => 'zone1']);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Purged all per zone successfully.');

        Queue::assertPushed(PurgeEverythingForZone::class, function ($job) {
            return $job->getZone() === 'zone1';
        });
    }

    public function testSuccessfulPurgeWithoutQueue()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);
        $cloudflareMock->shouldReceive('shouldQueue')
            ->once()
            ->andReturn(false);

        $apiMock = Mockery::mock();
        $cloudflareMock->shouldReceive('Api')
            ->once()
            ->andReturn($apiMock);

        $zonesMock = Mockery::mock();
        $apiMock->shouldReceive('Zones')
            ->once()
            ->andReturn($zonesMock);

        $zonesMock->shouldReceive('cachePurgeEverything')
            ->with('zone1')
            ->once()
            ->andReturnNull();

        $this->asAdmin();
        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeAllPerZone'), ['selected_zone' => 'zone1']);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Purged all per zone successfully.');
    }
}