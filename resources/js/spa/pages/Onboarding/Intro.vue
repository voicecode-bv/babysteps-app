<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Spinner from '@/components/Spinner.vue';
import OnboardingFeedPreview from '@/spa/components/OnboardingFeedPreview.vue';
import { useTranslations } from '@/spa/composables/useTranslations';
import { externalApi } from '@/spa/http/externalApi';
import { trackOnboardingStep } from '@/spa/http/onboarding';
import { belongsToAnotherPersonsCircle } from '@/spa/router/onboardingResume';
import { useCirclesStore } from '@/spa/stores/circles';
import type { Circle } from '@/spa/stores/circles';
import { useDefaultCirclesStore } from '@/spa/stores/defaultCircles';

const { t } = useTranslations();
const router = useRouter();
const circles = useCirclesStore();
const processing = ref(false);

// Record that the user opened the first onboarding screen; the matching
// 'completed' fires when they continue. The gap between the two is the
// screen's drop-off.
onMounted(() => trackOnboardingStep('intro', 'reached'));

// Self-healing fallback: register normally creates the "Family" circle, but
// OAuth signups never pass through that bootstrap and the register-time call
// is best-effort. Creating it here (and marking it as the default circle for
// new posts) keeps every signup path on the full onboarding.
async function createFamilyCircle(): Promise<Circle | null> {
    try {
        const created = await externalApi.post<{ data: Circle }>('/circles', {
            name: t('Family'),
        });

        circles.prepend(created.data);

        try {
            await externalApi.put('/default-circles', {
                circle_ids: [created.data.id],
            });
            useDefaultCirclesStore().invalidate();
        } catch {
            // Default-circle assignment is a convenience; the circle itself
            // is what the next steps need.
        }

        return created.data;
    } catch {
        return null;
    }
}

// The "Family" circle is already created by the API at registration, so we
// load the circles and jump straight to the first-moment step for that circle.
// If the circle is missing (OAuth signup or a failed register bootstrap), we
// create it here after all; only if that fails too do we skip the circle
// steps towards notifications.
async function continueOnboarding(): Promise<void> {
    if (processing.value) {
        return;
    }

    trackOnboardingStep('intro', 'completed');
    processing.value = true;

    try {
        const items = await circles.refresh();
        // Only a circle the user OWNS will accept their children and rules; a
        // fresh account can already be a member of someone else's circle
        // (redeemed invite link, linked OAuth account). Such an invited member
        // already belongs to a circle, so we create no redundant "Family"
        // circle for them — and since the children/first-moment steps need an
        // owned circle, they fall through to notifications below.
        const familyCircle =
            items.find((c) => c.is_owner) ??
            (belongsToAnotherPersonsCircle(items)
                ? null
                : await createFamilyCircle());

        if (familyCircle) {
            await router.push({
                name: 'spa.onboarding.first-moment',
                params: { circle: familyCircle.id },
            });
        } else {
            await router.push({ name: 'spa.onboarding.notifications' });
        }
    } catch {
        await router.push({ name: 'spa.onboarding.notifications' });
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div
        class="nativephp-safe-area relative isolate flex min-h-dvh flex-col overflow-hidden bg-sand px-6 text-ink"
    >
        <!-- Quiet atmosphere on the warm sand: two soft glows and film grain. -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10"
        >
            <div
                class="absolute -top-20 -right-16 size-72 rounded-full bg-sage-200/40 blur-3xl"
            ></div>
            <div
                class="absolute bottom-10 -left-20 size-72 rounded-full bg-accent-soft/20 blur-3xl"
            ></div>
            <div class="absolute inset-0 grain opacity-[0.04]"></div>
        </div>

        <div
            class="relative flex flex-1 flex-col items-center justify-center py-12"
        >
            <div class="mb-10 text-center">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-success-soft px-3 py-1 text-xs font-medium text-success-ink shadow-sm"
                >
                    {{ t('How it works') }}
                </span>
                <h1
                    class="mt-3 text-4xl font-extrabold tracking-tight text-ink"
                >
                    {{ t('Welcome to innerr') }}
                </h1>
                <p class="mt-3 text-ink-muted">
                    {{
                        t(
                            'Every photo you add makes your family feed warmer and more alive.',
                        )
                    }}
                </p>
            </div>

            <OnboardingFeedPreview class="mt-4 w-full" />
        </div>

        <div class="relative pt-2 pb-8">
            <button
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-action py-3.5 font-semibold text-white shadow-sm transition-colors hover:bg-action-hover disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="processing"
                @click="continueOnboarding"
            >
                <Spinner v-if="processing" class="size-4" />
                {{ t('Continue') }}
            </button>
        </div>
    </div>
</template>
