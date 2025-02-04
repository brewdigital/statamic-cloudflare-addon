<?php

namespace BrewDigital\StatamicCloudflareAddon\Helpers;

use BrewDigital\StatamicCloudflareAddon\Cloudflare;
use BrewDigital\StatamicCloudflareAddon\Jobs\PurgeZoneUrls;
use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;
use Statamic\Facades\Blueprint as BlueprintAPI;
use Statamic\Facades\Entry;

class CloudflareHelper
{
    /**
     * Invalidate a specified URL within a Cloudflare zone.
     *
     * This method parses the given URL to extract the domain and checks if a zone is provided.
     * If no zone is provided, it attempts to retrieve the zone using the domain. The method
     * invalidates the URL within the specified or retrieved zone by either dispatching the
     * purge job to the queue or executing it synchronously.
     *
     * @param string $url The URL to be invalidated.
     * @param string|null $zoneId The Cloudflare zone in which to invalidate the URL. If null, the zone is determined from the URL.
     * @return array
     */
    public function invalidateUrl(string $url, string $zoneId = null): array
    {
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'] ?? null;
        $errors = [];

        if (!$zoneId) {
            try {
                $zone = Cloudflare::zones()->get("www.{$domain}");
            } catch (\Exception $e) {
                $errors[] = 'Failed to retrieve zone for domain: ' . $domain . '. Error: ' . $e->getMessage();
                return $errors;
            }
        }

        $path = $parsedUrl['path'] ?? '';
        try {
            if (Cloudflare::shouldQueue()) {
                PurgeZoneUrls::dispatch($zoneId, [$path]);
            } else {
                PurgeZoneUrls::dispatchSync($zoneId, [$path]);
            }
        } catch (\Exception $e) {
            $errors[] = 'Failed to invalidate URL: ' . $url . '. Error: ' . $e->getMessage();
        }

        return $errors;
    }
    /**
     * Invalidate all entries within specified collections for a Cloudflare zone.
     *
     * This method iterates over the provided collections and retrieves all entries for each collection.
     * It invalidates the URL of each entry within the specified or default zone by calling the
     * `invalidateUrl` method. The method returns an array indicating the success of the invalidation
     * process for the collections.
     *
     * @param array $collections The collections whose entries are to be invalidated.
     * @param string|null $zoneId The Cloudflare zone in which to invalidate the entries. If null, the default zone is used.
     * @return array
     */
    public function invalidateCollections(array $collections, string $zoneId = null): array
    {
        $errors = [];
        foreach ($collections as $collection) {
            $entries = Entry::whereCollection($collection);
            foreach ($entries as $entry) {
                $url = $entry->absoluteUrl();
                if ($url) {
                    $urlErrors = $this->invalidateUrl($url, $zoneId);
                    $errors = array_merge($errors, $urlErrors);
                }
            }
        }

        return $errors;
    }

    /**
     * Load and configure a blueprint with additional options and default values from a configuration file.
     *
     * This method parses the blueprint YAML file to create a blueprint object. It then checks for a
     * configuration file, and if present, parses it to obtain default values. The method iterates over
     * the fields of the blueprint, setting options and default values based on the provided additional
     * options and configuration data. It preprocesses the fields and returns an array containing the
     * blueprint for publishing, metadata, and field values.
     *
     * @param string $blueprintPath The file path to the blueprint YAML.
     * @param array $additionalOptions Additional options to configure specific fields in the blueprint.
     * @param string|null $configPath The file path to the configuration YAML, used to set default values.
     * @return array An array containing the configured blueprint, field metadata, and values.
     */
    public function loadBlueprintWithConfig(string $blueprintPath, array $additionalOptions = [], string $configPath = null): array
    {
        $yaml = Yaml::parseFile($blueprintPath);
        $blueprint = BlueprintAPI::make()->setContents($yaml);

        $defaultValues = [];
        if ($configPath && file_exists($configPath)) {
            $configYaml = Yaml::parseFile($configPath);
            $defaultValues = $configYaml ?? [];
        }

        $fields = $blueprint->fields();

        foreach ($fields->all() as $field) {
            $handle = $field->handle();
            $fieldConfig = $field->config();

            if (isset($additionalOptions[$handle])) {
                $fieldConfig['options'] = $additionalOptions[$handle];
            }

            if (isset($defaultValues[$handle])) {
                $fieldConfig['default'] = $defaultValues[$handle];
            }

            $field->setConfig($fieldConfig);
        }

        $fields = $fields->addValues($defaultValues)->preProcess();

        return [
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ];
    }
}