<?php

namespace BrewDigital\StatamicCloudflareAddon\Tests\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeZoneUrls;
use BrewDigital\StatamicCloudflareAddon\Tests\TestCase;
use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Mockery;
use Illuminate\Support\Facades\Queue;

class PurgeUrlPerZoneTest extends TestCase
{
    public function testPurgeUrlPerZoneSuccessWithQueue()
    {
        Queue::fake();
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $cloudflareMock->shouldReceive('shouldQueue')
            ->once()
            ->andReturn(true);

        $data = [
            'zones' => ['zone1'],
            'url' => '/test'
        ];
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeUrlPerZone'), $data);

        Queue::assertPushed(PurgeZoneUrls::class, function ($job) use ($data) {
            return $job->getZone() === 'zone1';
        });
    }

    public function testPurgeUrlPerZoneSuccessWithoutQueue()
    {
        Queue::fake();
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $cloudflareMock->shouldReceive('shouldQueue')
            ->once()
            ->andReturn(false);

        $data = [
            'zones' => ['zone1'],
            'url' => '/test'
        ];
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeUrlPerZone'), $data);

        $response->assertRedirect();

        $response->assertSessionHas('success', 'Purged URL successfully.');
    }

    public function testPurgeUrlNotConfigured()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);

        $this->asAdmin();
        $this->post(route('statamic.cp.utilities.cloudflare.purgeUrlPerZone'), ['zones' => ['zone1'], 'url' => '/test'])
            ->assertSessionHas('error', 'The Cloudflare addon is not enabled or configured for this environment.');
    }
    public function testPurgeUrlValidationTest()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $this->asAdmin();
        $this->post(route('statamic.cp.utilities.cloudflare.purgeUrlPerZone'), [])
            ->assertSessionHasErrors(['zones', 'url']);
    }
}