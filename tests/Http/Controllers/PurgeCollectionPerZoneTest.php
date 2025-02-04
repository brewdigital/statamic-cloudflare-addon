<?php

namespace BrewDigital\StatamicCloudflareAddon\Tests\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeZoneUrls;
use BrewDigital\StatamicCloudflareAddon\Tests\TestCase;
use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Mockery;
use Illuminate\Support\Facades\Queue;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

class PurgeCollectionPerZoneTest extends TestCase
{

    public function testPurgeCollectionsPerZoneSuccessWithQueue()
    {
        Queue::fake();
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $cloudflareMock->shouldReceive('shouldQueue')
            ->twice()
            ->andReturn(true);

        $collection = Collection::make('collection1')->routes('/{slug}')->save();
        $entry1 = Entry::make()->collection('collection1')->slug('entry1')->data(['title' => 'Entry 1'])->locale('default')->save();
        $entry2 = Entry::make()->collection('collection1')->slug('entry2')->data(['title' => 'Entry 2'])->locale('default')->save();


        $data = [
            'zones' => ['zone1'],
            'collections' => ['collection1']
        ];
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeCollectionsPerZone'), $data);

        Queue::assertPushed(PurgeZoneUrls::class, function ($job) use ($data) {
            return $job->getZone() === 'zone1';
        });
    }

    public function testPurgeCollectionsPerZoneSuccessWithoutQueue()
    {
        $this->withoutExceptionHandling();
        Queue::fake();
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $cloudflareMock->shouldReceive('shouldQueue')
            ->twice()
            ->andReturn(false);

        $collection = Collection::make('collection1')->routes('/{slug}')->save();
        $entry1 = Entry::make()->collection('collection1')->slug('entry1')->data(['title' => 'Entry 1'])->locale('default')->save();
        $entry2 = Entry::make()->collection('collection1')->slug('entry2')->data(['title' => 'Entry 2'])->locale('default')->save();


        $data = [
            'zones' => ['zone1'],
            'collections' => ['collection1']
        ];
        $this->asAdmin();

        $response = $this->post(route('statamic.cp.utilities.cloudflare.purgeCollectionsPerZone'), $data);

        $response->assertRedirect();

        $response->assertSessionHas('success', 'Purged collections successfully.');

    }

    public function testPurgeCollectionsNotConfigured()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);

        $this->asAdmin();
        $this->post(route('statamic.cp.utilities.cloudflare.purgeCollectionsPerZone'), ['zones' => ['zone1'], 'collections' => ['collection1']])
            ->assertSessionHas('error', 'The Cloudflare addon is not enabled or configured for this environment.');
    }
    public function testPurgeCollectionsValidationTest()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $this->asAdmin();
        $this->post(route('statamic.cp.utilities.cloudflare.purgeCollectionsPerZone'), [])
            ->assertSessionHasErrors(['zones', 'collections']);
    }
}