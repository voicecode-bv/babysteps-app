import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { openPostWithHeroTransition } from './postHeroTransition';

type Transition = {
    ready: Promise<void>;
    finished: Promise<void>;
    updateCallbackDone: Promise<void>;
};

afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    document.body.innerHTML = '';
});

beforeEach(() => {
    // prefersReducedMotion() must be false for the morph path to run.
    vi.stubGlobal('matchMedia', vi.fn().mockReturnValue({ matches: false }));
});

describe('openPostWithHeroTransition', () => {
    it('skips the morph when no feed media element exists', async () => {
        vi.stubGlobal('CSS', { escape: (value: string) => value });
        const startViewTransition = vi.fn();
        document.startViewTransition =
            startViewTransition as unknown as typeof document.startViewTransition;

        const navigate = vi.fn().mockResolvedValue(undefined);

        await openPostWithHeroTransition('42', navigate);

        expect(navigate).toHaveBeenCalledOnce();
        expect(startViewTransition).not.toHaveBeenCalled();
    });

    it('swallows a rejected ready promise without leaving it unhandled', async () => {
        vi.stubGlobal('CSS', { escape: (value: string) => value });

        const media = document.createElement('div');
        media.setAttribute('data-post-media', '42');
        document.body.appendChild(media);

        // A skipped transition (e.g. duplicate `post-hero` names) rejects
        // `ready` with InvalidStateError while `finished` still resolves.
        const readyError = new DOMException(
            'Multiple elements found with view-transition-name: post-hero',
            'InvalidStateError',
        );
        const ready = Promise.reject<void>(readyError);
        const catchSpy = vi.spyOn(ready, 'catch');

        document.startViewTransition = ((update: () => Promise<void>) => {
            const updateCallbackDone = Promise.resolve(update()).then(
                () => undefined,
            );

            return {
                ready,
                finished: updateCallbackDone,
                updateCallbackDone,
            } satisfies Transition;
        }) as unknown as typeof document.startViewTransition;

        const navigate = vi.fn().mockResolvedValue(undefined);

        // Resolves cleanly: the rejected `ready` is caught internally, so it
        // never surfaces as an unhandled rejection.
        await expect(
            openPostWithHeroTransition('42', navigate),
        ).resolves.toBeUndefined();

        expect(navigate).toHaveBeenCalledOnce();
        expect(catchSpy).toHaveBeenCalled();
    });
});
