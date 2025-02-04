<?php
namespace BrewDigital\StatamicCloudflareAddon\Tests;

use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use BrewDigital\StatamicCloudflareAddon\ServiceProvider;
use Statamic\Extend\Manifest;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Illuminate\Support\Facades\View;
use Statamic\Statamic;
use Statamic\Facades\Site;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Composer::shouldReceive('installedVersion')->andReturn('v3.3');
        $this->withoutVite();
        View::addNamespace('statamic-cloudflare-addon', __DIR__.'/../resources/views');

        Site::setConfig([
            'sites' => [
                'default' => [
                    'name' => 'Default',
                    'locale' => 'en_US',
                    'url' => 'http://example.com'
                ],
            ],
        ]);
        $this->testConfigPath = resource_path('config/statamic-cloudflare.yaml');
        $directory = dirname($this->testConfigPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $entries = Entry::all();
        $entries->each(function ($entry) {
            $entry->delete();
        });

        $collections = Collection::all();

        $collections->each(function ($collection) {
            $collection->delete();
        });

    }

    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $appKey = 'base64:' . base64_encode(Str::random(32));
        $app['config']->set('app.key', $appKey);
        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.editions.pro', true);

        $app->make(Manifest::class)->manifest = [
            'brew-digital/statamic-cloudflare-addon' => [
                'id' => 'brew-digital/statamic-cloudflare-addon',
                'namespace' => 'BrewDigital\\StatamicCloudflareAddon\\',
                'autoload' => 'src',
                'provider' => ServiceProvider::class,
            ],
        ];
    }

    protected function asAdmin()
    {
        $user = User::make();
        $user->id(1)->email('brew@dev.com')->makeSuper();
        $this->be($user);

        return $user;
    }
}