import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp } from 'vue';

const redirectAfterAuth = vi.fn();
const bootstrap = vi.fn();
const replace = vi.fn();
const secureStorageSet = vi.fn();

let routeQuery: Record<string, unknown> = {};

const authStore: { token: string | null; bootstrap: typeof bootstrap } = {
    token: null,
    bootstrap,
};

vi.mock('vue-router', () => ({
    useRoute: () => ({ query: routeQuery }),
    useRouter: () => ({ replace }),
}));

vi.mock('@/spa/composables/useInviteRedeem', () => ({
    useInviteRedeem: () => ({ redirectAfterAuth }),
}));

vi.mock('@/spa/composables/useSecureStorage', () => ({
    secureStorage: { set: secureStorageSet },
    TOKEN_KEY: 'token',
}));

vi.mock('@/spa/composables/useTranslations', () => ({
    useTranslations: () => ({ t: (key: string) => key }),
}));

vi.mock('@/spa/stores/auth', () => ({
    useAuthStore: () => authStore,
}));

const OAuthCallback = (await import('./OAuthCallback.vue')).default;

function mountCallback(): void {
    createApp(OAuthCallback).mount(document.createElement('div'));
}

describe('OAuthCallback redirect', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        routeQuery = {};
        authStore.token = null;
        secureStorageSet.mockResolvedValue(undefined);
        redirectAfterAuth.mockResolvedValue(undefined);
    });

    it('sends a freshly created, not-yet-onboarded account into the onboarding flow', async () => {
        // This is the exact regression behind the production bug: a new Google
        // account (onboarded === false) must not land on the feed.
        routeQuery = { token: 'oauth-token' };
        bootstrap.mockResolvedValue({ user: { onboarded: false } });

        mountCallback();

        await vi.waitFor(() => expect(redirectAfterAuth).toHaveBeenCalled());
        expect(redirectAfterAuth).toHaveBeenCalledWith('/onboarding/intro');
    });

    it('sends an already-onboarded account straight to the feed', async () => {
        routeQuery = { token: 'oauth-token' };
        bootstrap.mockResolvedValue({ user: { onboarded: true } });

        mountCallback();

        await vi.waitFor(() => expect(redirectAfterAuth).toHaveBeenCalled());
        expect(redirectAfterAuth).toHaveBeenCalledWith('/');
    });

    it('fails to login when the callback carries no token', async () => {
        routeQuery = {};

        mountCallback();

        await vi.waitFor(() => expect(replace).toHaveBeenCalled());
        expect(replace).toHaveBeenCalledWith({
            name: 'spa.login',
            query: { oauth_error: 'missing_token' },
        });
        expect(bootstrap).not.toHaveBeenCalled();
    });

    it('forwards an OAuth provider error without attempting bootstrap', async () => {
        routeQuery = { error: 'oauth_failed' };

        mountCallback();

        await vi.waitFor(() => expect(replace).toHaveBeenCalled());
        expect(replace).toHaveBeenCalledWith({
            name: 'spa.login',
            query: { oauth_error: 'oauth_failed' },
        });
        expect(bootstrap).not.toHaveBeenCalled();
    });
});
