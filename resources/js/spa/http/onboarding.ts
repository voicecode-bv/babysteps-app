import { externalApi } from '@/spa/http/externalApi';
import { attribution } from '@/spa/services/attribution';
import { useAuthStore } from '@/spa/stores/auth';

export type OnboardingStep =
    | 'intro'
    | 'add_children'
    | 'first_moment'
    | 'invite_members'
    | 'notifications';

// How the user left a step. 'reached' is sent when the screen opens, the
// terminal outcomes when they leave it: 'completed' (did the intended action)
// or 'skipped' (advanced past without it). The reached/terminal split is what
// makes the funnel's per-screen drop-off measurable, independent of the skip
// branches between steps.
export type OnboardingOutcome = 'reached' | 'completed' | 'skipped';

// Fire-and-forget: tracking a step must not block the onboarding flow. On a
// network error / 5xx we lose at most one data point; the user notices
// nothing.
export function trackOnboardingStep(
    step: OnboardingStep,
    outcome: OnboardingOutcome = 'completed',
): void {
    externalApi.post('/onboarding/steps', { step, outcome }).catch(() => {});

    // Mirror genuine funnel completions into the attribution SDK so ad networks
    // can optimise toward them. No-op off the native runtime. Only a real
    // completion counts: a 'reached' ping or a 'skipped' first moment (the
    // user tapped "Share later") must not fire a conversion event.
    if (outcome !== 'completed') {
        return;
    }

    // The registration signal also fires from the auth store at the earliest
    // authenticated moment (covering both email and Apple/Google sign-up, and
    // accounts that quit before this screen); firing again here is a de-duped
    // backstop in case that hook was missed.
    if (step === 'intro') {
        const userId = useAuthStore().user?.id;

        if (userId) {
            attribution.trackRegister(userId);
        }
    } else if (step === 'first_moment') {
        attribution.trackFirstMoment();
    }
}
