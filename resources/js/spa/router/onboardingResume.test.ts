import { describe, expect, it } from 'vitest';
import {
    belongsToAnotherPersonsCircle,
    firstOwnedCircleId,
    onboardingResumeNeedsCircle,
    onboardingResumeRoute,
} from './onboardingResume';

describe('onboardingResumeRoute', () => {
    it('falls back to the intro when nothing was tracked', () => {
        expect(onboardingResumeRoute(null, null)).toEqual({
            name: 'spa.onboarding.intro',
        });
        expect(onboardingResumeRoute(undefined, null)).toEqual({
            name: 'spa.onboarding.intro',
        });
    });

    it('resumes at first-moment after the intro', () => {
        expect(onboardingResumeRoute('intro', 'c-1')).toEqual({
            name: 'spa.onboarding.first-moment',
            params: { circle: 'c-1' },
        });
    });

    it('treats the legacy first_circle step like the intro', () => {
        expect(onboardingResumeRoute('first_circle', 'c-1')).toEqual({
            name: 'spa.onboarding.first-moment',
            params: { circle: 'c-1' },
        });
    });

    it('resumes at first-moment for the legacy add_children step', () => {
        expect(onboardingResumeRoute('add_children', 'c-1')).toEqual({
            name: 'spa.onboarding.first-moment',
            params: { circle: 'c-1' },
        });
    });

    it('resumes at notifications after first_moment', () => {
        expect(onboardingResumeRoute('first_moment', null)).toEqual({
            name: 'spa.onboarding.notifications',
        });
    });

    it('resumes at notifications for the legacy invite_members step and notifications', () => {
        expect(onboardingResumeRoute('invite_members', null)).toEqual({
            name: 'spa.onboarding.notifications',
        });
        expect(onboardingResumeRoute('notifications', null)).toEqual({
            name: 'spa.onboarding.notifications',
        });
    });

    it('falls back to the intro when a circle-bound step has no circle', () => {
        expect(onboardingResumeRoute('intro', null)).toEqual({
            name: 'spa.onboarding.intro',
        });
        expect(onboardingResumeRoute('add_children', null)).toEqual({
            name: 'spa.onboarding.intro',
        });
    });

    it('falls back to the intro on unknown step values', () => {
        expect(onboardingResumeRoute('something_new', null)).toEqual({
            name: 'spa.onboarding.intro',
        });
    });
});

describe('firstOwnedCircleId', () => {
    it('skips circles the user is merely a member of', () => {
        expect(
            firstOwnedCircleId([
                { id: 'joined', is_owner: false },
                { id: 'mine', is_owner: true },
            ]),
        ).toBe('mine');
    });

    it('returns null when the user owns no circle', () => {
        expect(firstOwnedCircleId([{ id: 'joined', is_owner: false }])).toBe(
            null,
        );
        expect(firstOwnedCircleId([])).toBe(null);
    });
});

describe('belongsToAnotherPersonsCircle', () => {
    it('is true when the user is a member of a circle they do not own', () => {
        // An invite-link joiner: member of the inviter's circle, plus the
        // "Family" circle the intro step creates for them.
        expect(
            belongsToAnotherPersonsCircle([
                { is_owner: false },
                { is_owner: true },
            ]),
        ).toBe(true);
    });

    it('is false for a fresh organic signup that only owns its own circle', () => {
        expect(belongsToAnotherPersonsCircle([{ is_owner: true }])).toBe(false);
    });

    it('treats a missing is_owner flag as not owned', () => {
        expect(belongsToAnotherPersonsCircle([{}])).toBe(true);
    });

    it('is false with no circles at all', () => {
        expect(belongsToAnotherPersonsCircle([])).toBe(false);
    });
});

describe('onboardingResumeNeedsCircle', () => {
    it('needs a circle for the steps that resume into circle routes', () => {
        expect(onboardingResumeNeedsCircle('intro')).toBe(true);
        expect(onboardingResumeNeedsCircle('first_circle')).toBe(true);
        expect(onboardingResumeNeedsCircle('add_children')).toBe(true);
        // first_moment now resumes into notifications, which needs no circle.
        expect(onboardingResumeNeedsCircle('first_moment')).toBe(false);
        expect(onboardingResumeNeedsCircle('invite_members')).toBe(false);
        expect(onboardingResumeNeedsCircle(null)).toBe(false);
        expect(onboardingResumeNeedsCircle(undefined)).toBe(false);
    });
});
