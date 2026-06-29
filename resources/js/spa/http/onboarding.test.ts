import { beforeEach, expect, it, vi } from 'vitest';
import { trackOnboardingStep } from './onboarding';

const apiPost = vi.fn();
const trackRegister = vi.fn();
const trackFirstMoment = vi.fn();

vi.mock('@/spa/http/externalApi', () => ({
    externalApi: {
        post: (path: string, body: object) => apiPost(path, body),
    },
}));

vi.mock('@/spa/services/attribution', () => ({
    attribution: {
        trackRegister: (id: string) => trackRegister(id),
        trackFirstMoment: () => trackFirstMoment(),
    },
}));

vi.mock('@/spa/stores/auth', () => ({
    useAuthStore: () => ({ user: { id: 'user-1' } }),
}));

beforeEach(() => {
    apiPost.mockReset();
    apiPost.mockResolvedValue(undefined);
    trackRegister.mockReset();
    trackFirstMoment.mockReset();
});

it('posts the step and outcome to the API', () => {
    trackOnboardingStep('add_children', 'skipped');

    expect(apiPost).toHaveBeenCalledWith('/onboarding/steps', {
        step: 'add_children',
        outcome: 'skipped',
    });
});

it('defaults the outcome to completed', () => {
    trackOnboardingStep('intro');

    expect(apiPost).toHaveBeenCalledWith('/onboarding/steps', {
        step: 'intro',
        outcome: 'completed',
    });
});

it('fires the registration conversion when intro is completed', () => {
    trackOnboardingStep('intro', 'completed');

    expect(trackRegister).toHaveBeenCalledWith('user-1');
});

it('does not fire a conversion when a step is only reached', () => {
    trackOnboardingStep('intro', 'reached');

    expect(trackRegister).not.toHaveBeenCalled();
});

it('fires the first-moment conversion only on a real completion', () => {
    trackOnboardingStep('first_moment', 'completed');
    expect(trackFirstMoment).toHaveBeenCalledTimes(1);

    trackFirstMoment.mockReset();

    // "Share later" records a skip and must not count as a conversion.
    trackOnboardingStep('first_moment', 'skipped');
    expect(trackFirstMoment).not.toHaveBeenCalled();
});
