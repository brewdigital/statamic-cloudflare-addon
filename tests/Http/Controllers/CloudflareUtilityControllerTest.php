<?php


namespace BrewDigital\StatamicCloudflareAddon\Tests\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use Mockery;
use BrewDigital\StatamicCloudflareAddon\Tests\TestCase;
use Statamic\Facades\Collection;

class CloudflareUtilityControllerTest extends TestCase
{
    public function testShowPurgeAllRoute()
    {
        $mock = Mockery::mock('alias:' . Cloudflare::class);
        $mock->shouldReceive('loadConfig')
            ->andReturn([
                'enable_in_local' => true,
                'email' => 'your-email@example.com',
                'key' => 'your-api-key',
                'zones' => [
                    ['domain' => 'example.com', 'zone_id' => 'your-zone-id']
                ]
            ]);

        $mock->shouldReceive('isConfigured')
            ->andReturn(true);
        $mock->shouldReceive('zones')
            ->once()
            ->andReturn(collect(['zone1' => 'Zone 1', 'zone2' => 'Zone 2']));

        $this->app->instance(Cloudflare::class, $mock);

        $this->asAdmin();

        $response = $this->get(route('statamic.cp.utilities.cloudflare.purgeAll'));

        $response->assertStatus(200);

        $response->assertViewIs('statamic-cloudflare-addon::purge_all');

        $response->assertViewHas('zones', collect(['zone1' => 'Zone 1', 'zone2' => 'Zone 2'])->flip());
    }

    public function testShowPurgeCollectionsPerZoneRoute()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('zones')
            ->once()
            ->andReturn(collect(['zone1' => 'Zone 1', 'zone2' => 'Zone 2']));

        $collection1 = Collection::make('blog')->title('Blog')->save();
        $collection2 = Collection::make('news')->title('News')->save();

        $cloudflareMock->shouldReceive('isConfigured')
            ->andReturn(true);

        $cloudflareHelperMock = Mockery::mock();
        $cloudflareHelperMock->shouldReceive('loadBlueprintWithConfig')
            ->withArgs(function ($blueprintPath, $additionalOptions) {
                return strpos($blueprintPath, 'purge_collections.yaml') !== false &&
                    isset($additionalOptions['zones']) &&
                    isset($additionalOptions['collections']);
            })
            ->andReturn([
                'blueprint' => [],
                'meta' => [],
                'values' => [],
            ]);

        $this->app->instance('cloudflareHelper', $cloudflareHelperMock);

        $this->asAdmin();

        $response = $this->get(route('statamic.cp.utilities.cloudflare.purgeCollectionsPerZone'));

        $response->assertStatus(200);
        $response->assertViewIs('statamic-cloudflare-addon::purge_collection_per_zone');

        $response->assertViewHas('blueprint.values', function ($values) {
            return $values->has('zones') &&
                $values->get('zones') === [] &&
                $values->has('collections') &&
                $values->get('collections') === [];
        });

        $collection1->delete();
        $collection2->delete();
    }

    public function testShowPurgeUrlPerZoneRoute()
    {
        $cloudflareMock = Mockery::mock('alias:' . Cloudflare::class);
        $cloudflareMock->shouldReceive('zones')
            ->once()
            ->andReturn(collect(['zone1' => 'Zone 1', 'zone2' => 'Zone 2']));

        $cloudflareMock->shouldReceive('isConfigured')
            ->andReturn(true);
        $cloudflareHelperMock = Mockery::mock();
        $cloudflareHelperMock->shouldReceive('loadBlueprintWithConfig')
            ->withArgs(function ($blueprintPath, $additionalOptions) {
                return strpos($blueprintPath, 'purge_url.yaml') !== false &&
                    isset($additionalOptions['zones']);
            })
            ->andReturn([
                'blueprint' => [],
                'meta' => [],
                'values' => [],
            ]);

        $this->app->instance('cloudflareHelper', $cloudflareHelperMock);

        $this->asAdmin();

        $response = $this->get(route('statamic.cp.utilities.cloudflare.purgeUrlPerZone'));
        $response->assertStatus(200);
        $response->assertViewIs('statamic-cloudflare-addon::purge_url_per_zone');
        $response->assertViewHas('blueprint.meta', function ($meta) {
            return $meta->has('zones') &&
                $meta->get('zones')['options'] === [
                    ['value' => 'Zone 1', 'label' => 'zone1'],
                    ['value' => 'Zone 2', 'label' => 'zone2'],
                ];
        });

        $response->assertViewHas('blueprint.values', function ($values) {
            return $values->has('zones') &&
                $values->get('zones') === [];
        });
    }

    public function testShowSettingsRoute()
    {
        $cloudflareHelperMock = Mockery::mock();
        $cloudflareHelperMock->shouldReceive('loadBlueprintWithConfig')
            ->withArgs(function ($blueprintPath, $additionalOptions, $configPath) {
                return strpos($blueprintPath, 'settings.yaml') !== false &&
                    is_array($additionalOptions) &&
                    $configPath === resource_path('config/statamic-cloudflare.yaml');
            })
            ->andReturn([
                'blueprint' => [],
                'meta' => [],
                'values' => [],
            ]);

        $this->app->instance('cloudflareHelper', $cloudflareHelperMock);
        $this->asAdmin();

        $response = $this->get(route('statamic.cp.utilities.cloudflare.settings'));
        $response->assertStatus(200);

        $response->assertViewIs('statamic-cloudflare-addon::settings');
        $response->assertViewHas('blueprint', function ($blueprint) {
            return is_array($blueprint);
        });
    }



    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}