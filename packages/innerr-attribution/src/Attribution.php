<?php

namespace Innerr\Attribution;

/**
 * PHP-side facade target for Attribution.
 *
 * Attribution events are normally fired from the JS bridge (see
 * resources/js/index.ts and resources/js/spa/services/attribution.ts), which is
 * where the SKAdNetwork conversion window lives. These helpers exist so queued
 * jobs or server-rendered flows can fire the same events, and so the plugin
 * follows the standard NativePHP plugin shape.
 *
 * The plugin runs SKAdNetwork-only with no IDFA/ATT prompt, in line with the
 * app's privacy positioning.
 */
class Attribution
{
    /**
     * Initialise the Singular SDK. Idempotent; safe to call once at app start.
     */
    public function init(): bool
    {
        return $this->call('Attribution.Init', []);
    }

    /**
     * Track a conversion event.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function event(string $name, ?float $value = null, ?string $currency = null, array $attributes = []): bool
    {
        $parameters = ['name' => $name];

        if ($value !== null) {
            $parameters['value'] = $value;
        }

        if ($currency !== null) {
            $parameters['currency'] = $currency;
        }

        if ($attributes !== []) {
            $parameters['attributes'] = $attributes;
        }

        return $this->call('Attribution.Event', $parameters);
    }

    /**
     * Associate a hashed first-party user id for cross-device stitching.
     * Never pass raw PII (email, name); hash it upstream.
     */
    public function setUserId(string $userId): bool
    {
        return $this->call('Attribution.SetUserId', ['userId' => $userId]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function call(string $function, array $parameters): bool
    {
        if (! function_exists('nativephp_call')) {
            return false;
        }

        $result = nativephp_call($function, json_encode($parameters) ?: '{}');

        if (! $result) {
            return false;
        }

        $decoded = json_decode($result, true);

        return isset($decoded['success']) && $decoded['success'] === true;
    }
}
