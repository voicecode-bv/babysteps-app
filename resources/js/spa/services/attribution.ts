import { isNativeRuntime } from '@/spa/composables/usePlatform';
import { Attribution } from '@innerr/attribution';

/**
 * App-level attribution wrapper around the Singular bridge plugin.
 *
 * Measurement must never throw, never block, and never matter when it does not
 * fire (web build, simulator, bridge unavailable). Every helper is therefore a
 * synchronous fire-and-forget that swallows bridge errors, mirroring the
 * haptics service.
 *
 * Event names map to Singular's standard tokens where one exists so the ad
 * networks (Google/Meta) can optimise toward them; `invite_sent` is a custom
 * event specific to Innerr's funnel.
 */
function fire(call: () => Promise<void>): void {
    if (!isNativeRuntime()) {
        return;
    }

    try {
        void call().catch(() => {
            /* bridge unavailable: attribution event silently skipped */
        });
    } catch {
        /* synchronous bridge failure: equally non-fatal */
    }
}

export const attribution = {
    /**
     * Initialise the SDK. Call once, as early as possible in app bootstrap, so
     * the SKAdNetwork install window registers promptly.
     */
    init(): void {
        fire(() => Attribution.init());
    },

    /**
     * Stitch a hashed first-party user id (never raw PII).
     */
    setUserId(hashedUserId: string): void {
        fire(() => Attribution.setUserId(hashedUserId));
    },

    /**
     * Registration complete — fired at most once per user on this device.
     *
     * The earliest post-auth hook fires this for any brand-new, not-yet-
     * onboarded account (so it also catches the accounts that close the app
     * before the onboarding intro), and the intro step fires it again as a
     * backstop. The per-user localStorage guard means the same user is never
     * counted twice, while two accounts on one device still each report once.
     */
    trackRegister(userId: string): void {
        const key = `innerr.attribution.registered:${userId}`;

        try {
            if (window.localStorage?.getItem(key)) {
                return;
            }

            window.localStorage?.setItem(key, '1');
        } catch {
            // localStorage blocked: prefer a possible duplicate over a missed
            // registration signal, so fall through and fire.
        }

        fire(() => Attribution.event('sng_complete_registration'));
    },

    /** First moment/post created — the core activation event. */
    trackFirstMoment(): void {
        fire(() => Attribution.event('sng_content_view'));
    },

    /** A circle invite link was shared. */
    trackInviteSent(): void {
        fire(() => Attribution.event('invite_sent'));
    },
};
