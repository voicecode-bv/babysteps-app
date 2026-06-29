import type { RouteLocationRaw } from 'vue-router';

/* Maps the furthest completed onboarding step (as reported by the API via the
   bootstrap payload) to the route the user should resume at: the step AFTER
   the last one they finished. Unknown or missing values fall back to the
   intro, which matches the pre-resume behaviour.

   The forced flow is now intro -> first-moment -> notifications; adding
   children and inviting members moved out of onboarding into the in-app
   getting-started card. 'first_circle' and 'add_children' are legacy steps
   from the longer flow; a user who tracked them resumes at the first step that
   still exists after their position. */

/* The onboarding steps add children and rules to a circle, which requires
   owning it. A fresh account can already be a member of other people's
   circles (invite link redeemed before onboarding, linked OAuth account), so
   picking the first circle from the list is not safe. */
export function firstOwnedCircleId(
    circles: ReadonlyArray<{ id: string; is_owner?: boolean }>,
): string | null {
    return circles.find((circle) => circle.is_owner)?.id ?? null;
}

/* Someone who joined through another person's invite link already belongs to a
   circle, which makes the invite-members onboarding step redundant for them.
   They may also own a freshly created "Family" circle, so "owns a circle" is
   not the signal — membership in a circle they do NOT own is. This is durable
   (server-side circle membership) and so survives app restarts and onboarding
   resume, unlike the transient invite token kept in storage. */
export function belongsToAnotherPersonsCircle(
    circles: ReadonlyArray<{ is_owner?: boolean }>,
): boolean {
    return circles.some((circle) => !circle.is_owner);
}

export function onboardingResumeNeedsCircle(
    step: string | null | undefined,
): boolean {
    // These resume into the first-moment screen, which is bound to an owned
    // circle. first_moment now resumes into notifications, which is not.
    return (
        step === 'intro' || step === 'first_circle' || step === 'add_children'
    );
}

export function onboardingResumeRoute(
    step: string | null | undefined,
    circleId: string | null,
): RouteLocationRaw {
    switch (step) {
        // intro and the legacy children steps all resume into the first-moment
        // screen, the next step that still exists in the shortened flow.
        case 'intro':
        case 'first_circle':
        case 'add_children':
            return circleId
                ? {
                      name: 'spa.onboarding.first-moment',
                      params: { circle: circleId },
                  }
                : { name: 'spa.onboarding.intro' };
        // After the first moment, notifications is the final step. invite_members
        // is a legacy step that sat between the two; users who tracked it also
        // resume here.
        case 'first_moment':
        case 'invite_members':
        case 'notifications':
            return { name: 'spa.onboarding.notifications' };
        default:
            return { name: 'spa.onboarding.intro' };
    }
}
