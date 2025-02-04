<?php

namespace BrewDigital\StatamicCloudflareAddon\Http\Controllers;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use BrewDigital\StatamicCloudflareAddon\Helpers\CloudflareHelper;
use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeEverythingForZone;
use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeEverythingForAllZones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Facades\Collection;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Validation\ValidationException;

class CloudflareUtilityController extends CpController
{
    protected CloudflareHelper $cloudflareHelper;

    public function __construct(CloudflareHelper $cloudflareHelper)
    {
        $this->cloudflareHelper = $cloudflareHelper;
    }

    /**
     * Display the view for purging all cache.
     *
     * This method retrieves the available Cloudflare zones using the CloudflareHelper
     * and passes them to the 'purge_all' view for rendering.
     *
     * @return View The view displaying the purge all cache options.
     */
    public function showPurgeAll(): View
    {
        $zones = Cloudflare::zones();
        return view('statamic-cloudflare-addon::purge_all', [
            'zones' => $zones ? $zones->flip() : [],
        ]);
    }

    /**
     * Display the view for purging collections per Cloudflare zone.
     *
     * This method constructs the path to the blueprint file and retrieves the available
     * Cloudflare zones and collections. It loads the blueprint with the configuration
     * options for zones and collections and passes it to the 'purge_collection_per_zone' view.
     *
     * @return View The view displaying the options for purging collections per zone.
     */
    public function showPurgeCollectionsPerZone(): View
    {
        $addonResourcesPath = __DIR__ . '/../../../resources/blueprints/';
        $blueprintPath = $addonResourcesPath . 'purge_collections.yaml';

        $collectionOptions = Collection::all()->mapWithKeys(function ($collection) {
            return [$collection->handle() => $collection->title()];
        })->toArray();

        $zones = Cloudflare::zones();

        return view('statamic-cloudflare-addon::purge_collection_per_zone', [
            'blueprint' => $this->cloudflareHelper->loadBlueprintWithConfig($blueprintPath, [
                'zones' => $zones ? $zones->flip() : [],
                'collections' => $collectionOptions,
            ]),
        ]);
    }

    /**
     * Display the view for purging URLs per Cloudflare zone.
     *
     * This method constructs the path to the blueprint file and retrieves the available
     * Cloudflare zones. It loads the blueprint with the configuration options for zones
     * and passes it to the 'purge_url_per_zone' view.
     *
     * @return View The view displaying the options for purging URLs per zone.
     */
    public function showPurgeUrlPerZone(): View
    {
        $addonResourcesPath = __DIR__ . '/../../../resources/blueprints/';
        $blueprintPath = $addonResourcesPath . 'purge_url.yaml';

        $zones = Cloudflare::zones();

        $blueprint = $this->cloudflareHelper->loadBlueprintWithConfig($blueprintPath, [
            'zones' => $zones ? $zones->flip() : [],
        ]);

        return view('statamic-cloudflare-addon::purge_url_per_zone', [
            'blueprint' => $blueprint,
        ]);
    }

    /**
     * Display the settings view for the Cloudflare addon.
     *
     * This method constructs the path to the settings blueprint file and the configuration file.
     * It loads the blueprint with the configuration from the config file and passes it to the
     * 'settings' view along with the blueprint metadata and values.
     *
     * @return View The view displaying the settings for the Cloudflare addon.
     */
    public function showSettings(): View
    {
        $addonResourcesPath = __DIR__ . '/../../../resources/blueprints/';
        $blueprintPath = $addonResourcesPath . 'settings.yaml';

        $configPath = resource_path('config/statamic-cloudflare.yaml');

        $blueprint = $this->cloudflareHelper->loadBlueprintWithConfig($blueprintPath, [], $configPath);

        return view('statamic-cloudflare-addon::settings', [
            'blueprint' => $blueprint,
            'meta' => $blueprint['meta'],
            'values' => $blueprint['values'],
        ]);
    }

    /**
     * Handle the request to purge all cache per Cloudflare zone.
     *
     * This method checks if the Cloudflare addon is configured and enabled. It retrieves the selected
     * zone ID from the request and attempts to purge all cache for that zone. If queuing is enabled,
     * the purge job is dispatched to the queue; otherwise, it is executed immediately. The method
     * handles any errors that occur during the purge process and returns the appropriate response.
     *
     * @param Request $request The incoming request containing the selected zone.
     * @return RedirectResponse A redirect response indicating the result of the purge operation.
     */
    public function purgeAllPerZone(Request $request): RedirectResponse
    {
        if (!Cloudflare::isConfigured()) {
            return redirect()->back()->with('error', 'The Cloudflare addon is not enabled or configured for this environment.');
        }
        $zone_id = $request->selected_zone;

        if (!isset($zone_id)){
            return redirect()->back()->with('error', 'Zones are not configured.');
        }

        session()->flash('selected_zone', $zone_id);

        try {
            if (Cloudflare::shouldQueue()) {
                PurgeEverythingForZone::dispatch($zone_id);
            } else {
                PurgeEverythingForZone::dispatchSync($zone_id);
            }
        }catch (\Exception $e) {
            \Log::error(['An error occurred while purging all per zone.', $e->getMessage()]);
            return redirect()->back()->with('error', 'An error occurred while purging all per zone.');
        }

        return redirect()->back()->with('success', 'Purged all per zone successfully.');
    }

    /**
     * Handle the request to purge all cache across all Cloudflare zones.
     *
     * This method checks if the Cloudflare addon is configured and enabled. It retrieves all available
     * Cloudflare zones and attempts to purge all cache for each zone. If queuing is enabled, each purge
     * job is dispatched to the queue; otherwise, each job is executed immediately. The method collects
     * any errors that occur during the purge process and returns the appropriate response.
     *
     * @return RedirectResponse A redirect response indicating the result of the purge operation.
     */
    public function purgeAll(): RedirectResponse
    {
        if (!Cloudflare::isConfigured()) {
            return redirect()->back()->with('error', 'The Cloudflare addon is not enabled or configured for this environment.');
        }

        $zones = Cloudflare::zones();

        if (!isset($zones) || $zones->isEmpty()) {
            return redirect()->back()->with('error', 'Zones are not configured.');
        }
        try {
            if (Cloudflare::shouldQueue()) {
                PurgeEverythingForAllZones::dispatch();
            } else {
                PurgeEverythingForAllZones::dispatchSync();
            }
        }catch (\Exception $e) {
            \Log::error(['An error occurred while purging all.', $e->getMessage()]);
           return redirect()->back()->with('error', 'An error occurred while purging all.');
        }

        return redirect()->back()->with('success', 'Purged all successfully.');
    }

    /**
     * Handle the request to purge specified collections per Cloudflare zone.
     *
     * This method checks if the Cloudflare addon is configured and enabled. It retrieves the selected
     * zones and collections from the request and invalidates the specified collections for each zone.
     * The method returns a response indicating the success of the purge operation.
     *
     * @param Request $request The incoming request containing selected zones and collections.
     * @return RedirectResponse A redirect response indicating the result of the purge operation.
     */
    public function purgeCollectionsPerZone(Request $request): RedirectResponse
    {
        if (!Cloudflare::isConfigured()) {
            return redirect()->back()->with('error', 'The Cloudflare addon is not enabled or configured for this environment.');
        }
        $data = $request->validate([
            'zones' => 'required|array',
            'collections' => 'required|array',
        ]);

        $errors = [];

        foreach ($data['zones'] as $zone) {
            $zoneErrors = $this->cloudflareHelper->invalidateCollections($data['collections'], $zone);
            $errors = array_merge($errors, $zoneErrors);
        }

        if (!empty($errors)) {
            \Log::error(['An error occurred while purging collections per zone.', ['errors' => $errors]]);
            throw new \Exception('An error occurred while purging collections per zone.');
        }

        return redirect()->back()->with('success', 'Purged collections successfully.');
    }

    /**
     * Handle the request to purge a specified URL per Cloudflare zone.
     *
     * This method checks if the Cloudflare addon is configured and enabled. It retrieves the selected
     * zones and the URL from the request. The method invalidates the specified URL for each zone and
     * checks the result of each invalidation. If any invalidation fails, it returns an error response.
     * Otherwise, it returns a success response.
     *
     * @param Request $request The incoming request containing selected zones and the URL to purge.
     * @return RedirectResponse A redirect response indicating the result of the purge operation.
     */
    public function purgeUrlPerZone(Request $request): RedirectResponse
    {
        if (!Cloudflare::isConfigured()) {
            return redirect()->back()->with('error', 'The Cloudflare addon is not enabled or configured for this environment.');
        }
        $data = $request->validate([
            'zones' => 'required|array',
            'url' => 'required|string',
        ]);

        $errors = [];

        foreach ($data['zones'] as $zone) {
            $errors = $this->cloudflareHelper->invalidateUrl($data['url'], $zone);
        }

        if (!empty($errors)) {
            \Log::error(['An error occurred while purging url per zone.', $errors]);
            throw new \Exception('An error occurred while purging URL per zone.');
        }
        return redirect()->back()->with('success', 'Purged URL successfully.');
    }

    /**
     * Handle the request to update Cloudflare addon settings.
     *
     * This method validates the incoming request data for updating settings, ensuring required fields
     * are present and correctly formatted. It checks for the presence of a token or both an email and
     * key for authentication. The method attempts to update the configuration file with the new settings
     * and returns a response indicating the success or failure of the update operation.
     *
     * @param Request $request The incoming request containing settings data to update.
     * @return RedirectResponse A redirect response indicating the result of the update operation.
     * @throws ValidationException If validation fails for the provided data.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purge_everything_on_entry_save' => 'required|boolean',
            'queued' => 'required|boolean',
            'enable_in_local' => 'required|boolean',
            'enable_in_staging' => 'required|boolean',
            'enable_in_production' => 'required|boolean',
            'zones' => 'nullable|array',
            'zones.*.zone_id' => 'required_with:zones|string',
            'zones.*.domain' => 'required_with:zones|string',
        ]);

        if (empty($request->token) && (empty($request->email) || empty($request->key))) {
            throw ValidationException::withMessages([
                'token' => ['A token must be provided, or both an email and a key must be provided.'],
            ]);
        }

        try {
            $configPath = resource_path('config/statamic-cloudflare.yaml');
            $yamlContent = Yaml::dump(array_merge($data, $request->only(['token', 'email', 'key'])));
            file_put_contents($configPath, $yamlContent);

            return redirect()->back()->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            \Log::error(['An error occurred while updating settings.', $e->getMessage()]);
            return redirect()->back()->withErrors('An error occurred while updating settings.');
        }
    }
}
