<?php

namespace BrewDigital\StatamicCloudflareAddon\Tests\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Tests\TestCase;
use Illuminate\Support\Facades\File;
class UpdateSettingsTest extends TestCase
{

    public function testUpdateSettingsSuccess()
    {
        $requestData = [
            'purge_everything_on_entry_save' => true,
            'queued' => true,
            'enable_in_local' => true,
            'enable_in_staging' => true,
            'enable_in_production' => true,
            'zones' => [
                ['zone_id' => 'zone123', 'domain' => 'example.com'],
            ],
            'token' => 'some-token',
        ];

        if (File::exists($this->testConfigPath)) {
            File::delete($this->testConfigPath);
        }

        $this->asAdmin();
        $response = $this->post(route('statamic.cp.utilities.cloudflare.settings'), $requestData);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Settings updated successfully.');

        $this->assertTrue(File::exists($this->testConfigPath), 'The config file was not created.');

        $yamlContent = File::get($this->testConfigPath);
        $this->assertStringContainsString('some-token', $yamlContent, 'The token was not found in the config file.');

        File::delete($this->testConfigPath);
    }

    public function testUpdateSettingsValidationErrors()
    {
        $this->asAdmin();
        $response = $this->post(route('statamic.cp.utilities.cloudflare.settings'), []);

        $response->assertSessionHasErrors([
            'purge_everything_on_entry_save',
            'queued',
            'enable_in_local',
            'enable_in_staging',
            'enable_in_production',
        ]);
    }

    public function testUpdateSettingsTokenOrEmailKeyRequired()
    {
        $requestData = [
            'purge_everything_on_entry_save' => true,
            'queued' => true,
            'enable_in_local' => true,
            'enable_in_staging' => true,
            'enable_in_production' => true,
        ];
        $this->asAdmin();
        $response = $this->post(route('statamic.cp.utilities.cloudflare.settings'), $requestData);

        $response->assertSessionHasErrors(['token']);
    }

    public function testUpdateSettingsExceptionHandling()
    {
        $directory = dirname($this->testConfigPath);
        if (File::exists($directory)) {
            File::deleteDirectory($directory);
        }

        if (File::exists($this->testConfigPath)) {
            File::delete($this->testConfigPath);
        }

        $requestData = [
            'purge_everything_on_entry_save' => true,
            'queued' => true,
            'enable_in_local' => true,
            'enable_in_staging' => true,
            'enable_in_production' => true,
            'zones' => [
                ['zone_id' => 'zone123', 'domain' => 'example.com'],
            ],
            'token' => 'some-token',
        ];

        $this->asAdmin();
        $response = $this->post(route('statamic.cp.utilities.cloudflare.settings'), $requestData);

        $response->assertRedirect();
        $response->assertSessionHasErrors();

        $errors = session('errors')->getBag('default')->all();
        $this->assertContains('An error occurred while updating settings.', $errors);
    }

}