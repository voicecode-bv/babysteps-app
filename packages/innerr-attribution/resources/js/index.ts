/**
 * innerr/attribution — JS bridge for the Attribution plugin.
 *
 * Thin wrapper over the Singular SDK for privacy-first mobile measurement:
 * SKAdNetwork-only on iOS (no IDFA, no ATT prompt), Google Play Install Referrer
 * on Android. Apple Search Ads stays deterministic via Apple's own AdServices
 * framework and needs nothing here.
 *
 * Calls are fire-and-forget on the native side; rejections only occur when the
 * bridge itself is unavailable. App code should use the wrapper in
 * `resources/js/spa/services/attribution.ts`, which swallows those and gates on
 * the native runtime.
 */
import { BridgeCall } from '@nativephp/mobile';

export interface AttributionEventOptions {
    /** Optional monetary value (e.g. subscription price). */
    value?: number;
    /** ISO 4217 currency code, required when `value` is set (e.g. 'EUR'). */
    currency?: string;
    /** Extra non-PII attributes attached to the event. */
    attributes?: Record<string, string | number | boolean>;
}

/**
 * Initialise the Singular SDK. Idempotent on the native side; call once as early
 * as possible in app start so the SKAdNetwork install window registers promptly.
 */
export async function init(): Promise<void> {
    await BridgeCall('Attribution.Init', {});
}

/**
 * Track a conversion event. Prefer Singular's standard event tokens
 * (e.g. `sng_complete_registration`, `sng_content_view`) so ad networks can
 * optimise toward them.
 */
export async function event(
    name: string,
    options: AttributionEventOptions = {},
): Promise<void> {
    const params: Record<string, unknown> = { name };

    if (options.value !== undefined) {
        params.value = options.value;
    }
    if (options.currency !== undefined) {
        params.currency = options.currency;
    }
    if (options.attributes !== undefined) {
        params.attributes = options.attributes;
    }

    await BridgeCall('Attribution.Event', params);
}

/**
 * Associate a hashed first-party user id for cross-device stitching.
 * Never pass raw PII (email, name) — hash it before calling.
 */
export async function setUserId(userId: string): Promise<void> {
    await BridgeCall('Attribution.SetUserId', { userId });
}

export const Attribution = {
    init,
    event,
    setUserId,
};

export default Attribution;
