import { externalApi } from '@/spa/http/externalApi';
import { attribution } from '@/spa/services/attribution';

export type OnboardingStep =
    | 'intro'
    | 'add_children'
    | 'first_moment'
    | 'invite_members'
    | 'notifications';

// Fire-and-forget: tracking a step must not block the onboarding flow. On a
// network error / 5xx we lose at most one data point; the user notices
// nothing.
export function trackOnboardingStep(step: OnboardingStep): void {
    externalApi.post('/onboarding/steps', { step }).catch(() => {});

    // Mirror funnel steps into the attribution SDK so ad networks can optimise
    // toward them. No-op off the native runtime. `intro` is the first onboarding
    // step every brand-new account hits, regardless of sign-up method (email or
    // Apple/Google), so it is our reliable "registration complete" signal — the
    // dedicated /register endpoint is bypassed by the social-auth flows.
    if (step === 'intro') {
        attribution.trackRegister();
    } else if (step === 'first_moment') {
        attribution.trackFirstMoment();
    }
}
