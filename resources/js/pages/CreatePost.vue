<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { store } from '@/actions/App/Http/Controllers/PostActionController';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Circle {
    id: number;
    name: string;
    members_count: number;
}

const props = defineProps<{
    circles: Circle[];
}>();

const { t } = useTranslations();
const page = usePage();
const isNative = computed(() => page.props.platform !== 'web');

const form = useForm({
    media: null as File | null,
    caption: '',
    circle_ids: [] as number[],
});

const mediaPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const showSourcePicker = ref(false);

const isValidForm = computed(() => form.media !== null && form.circle_ids.length > 0);

function openSourcePicker() {
    if (isNative.value) {
        showSourcePicker.value = true;
    } else {
        selectFromGallery();
    }
}

function selectFromGallery() {
    showSourcePicker.value = false;
    if (fileInput.value) {
        fileInput.value.removeAttribute('capture');
        fileInput.value.click();
    }
}

function openCamera() {
    showSourcePicker.value = false;
    if (fileInput.value) {
        fileInput.value.setAttribute('capture', 'environment');
        fileInput.value.click();
    }
}

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    form.media = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        mediaPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function removeMedia() {
    form.media = null;
    mediaPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

const allCirclesSelected = computed(() => props.circles.length > 0 && form.circle_ids.length === props.circles.length);

function toggleAllCircles() {
    if (allCirclesSelected.value) {
        form.circle_ids = [];
    } else {
        form.circle_ids = props.circles.map((c) => c.id);
    }
}

function toggleCircle(circleId: number) {
    const index = form.circle_ids.indexOf(circleId);
    if (index === -1) {
        form.circle_ids.push(circleId);
    } else {
        form.circle_ids.splice(index, 1);
    }
}

function submit() {
    form.post(store.url(), {
        forceFormData: true,
    });
}
</script>

<template>
    <AppLayout :title="t('New post')">
        <template #header-left>
            <Link href="/" class="text-sand-700 dark:text-sand-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </Link>
        </template>
        <template #header-right>
            <button
                :disabled="!isValidForm || form.processing"
                class="text-sm font-semibold text-sand-600 disabled:opacity-40 dark:text-sand-400"
                @click="submit"
            >
                {{ t('Share') }}
            </button>
        </template>

        <div class="flex flex-col">
            <!-- Media Section -->
            <div class="border-b border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/jpg,image/png,image/gif"
                    class="hidden"
                    @change="handleFileChange"
                />

                <!-- Preview -->
                <div v-if="mediaPreview" class="relative">
                    <img :src="mediaPreview" class="w-full object-cover" :alt="t('Selected photo')" />
                    <button
                        class="absolute right-3 top-3 rounded-full bg-black/50 p-1.5 text-white"
                        @click="removeMedia"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Upload Placeholder -->
                <div v-else class="flex flex-col items-center justify-center px-8 py-16">
                    <button
                        class="flex flex-col items-center gap-3"
                        @click="openSourcePicker"
                    >
                        <div class="flex size-20 items-center justify-center rounded-full bg-sand-100 dark:bg-sand-800">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor" class="size-10 text-sand-400 dark:text-sand-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-sand-500 dark:text-sand-400">{{ t('Add a photo') }}</span>
                    </button>
                </div>

                <p v-if="form.errors.media" class="px-4 pb-3 text-xs text-blush-500">{{ form.errors.media }}</p>
            </div>

            <!-- Caption -->
            <div class="border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
                <textarea
                    v-model="form.caption"
                    :placeholder="t('Write a caption...')"
                    rows="3"
                    maxlength="2200"
                    class="w-full resize-none border-0 bg-transparent p-0 text-sm text-sand-800 placeholder-sand-400 focus:outline-none focus:ring-0 dark:text-sand-100 dark:placeholder-sand-500"
                />
                <p v-if="form.errors.caption" class="mt-1 text-xs text-blush-500">{{ form.errors.caption }}</p>
            </div>

            <!-- Circle Selection -->
            <div v-if="circles.length > 0" class="border-b border-sand-200 bg-white px-4 py-3 dark:border-sand-800 dark:bg-sand-900">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-medium text-sand-500 dark:text-sand-400">{{ t('Share with circles') }}</p>
                    <button
                        class="text-xs font-medium text-sand-500 dark:text-sand-400"
                        @click="toggleAllCircles"
                    >
                        {{ allCirclesSelected ? t('Deselect all') : t('Select all') }}
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="circle in circles"
                        :key="circle.id"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="form.circle_ids.includes(circle.id)
                            ? 'border-sand-500 bg-sand-500 text-white dark:border-sand-600 dark:bg-sand-600'
                            : 'border-sand-200 text-sand-600 dark:border-sand-700 dark:text-sand-400'"
                        @click="toggleCircle(circle.id)"
                    >
                        {{ circle.name }}
                    </button>
                </div>
                <p v-if="form.errors.circle_ids" class="mt-1 text-xs text-blush-500">{{ form.errors.circle_ids }}</p>
            </div>

            <!-- Upload Progress -->
            <div v-if="form.progress" class="bg-white px-4 py-3 dark:bg-sand-900">
                <div class="h-1.5 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-700">
                    <div
                        class="h-full rounded-full bg-sand-500 transition-all duration-300 dark:bg-sand-400"
                        :style="{ width: `${form.progress.percentage}%` }"
                    />
                </div>
                <p class="mt-1 text-center text-xs text-sand-500 dark:text-sand-400">
                    {{ form.progress.percentage }}%
                </p>
            </div>
        </div>

        <!-- Source Picker Overlay -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showSourcePicker" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="showSourcePicker = false">
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-full"
                        enter-to-class="translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0"
                        leave-to-class="translate-y-full"
                    >
                        <div v-if="showSourcePicker" class="w-full max-w-lg" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
                            <div class="mx-3 overflow-hidden rounded-xl bg-white dark:bg-sand-800">
                                <button
                                    class="flex w-full items-center gap-3 px-4 py-3.5 text-left text-sm font-medium text-sand-700 active:bg-sand-50 dark:text-sand-200 dark:active:bg-sand-700"
                                    @click="openCamera"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                    </svg>
                                    {{ t('Take a photo') }}
                                </button>
                                <div class="mx-4 border-t border-sand-100 dark:border-sand-700" />
                                <button
                                    class="flex w-full items-center gap-3 px-4 py-3.5 text-left text-sm font-medium text-sand-700 active:bg-sand-50 dark:text-sand-200 dark:active:bg-sand-700"
                                    @click="selectFromGallery"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-sand-500 dark:text-sand-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21ZM8.25 8.625a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                                    </svg>
                                    {{ t('Choose from gallery') }}
                                </button>
                            </div>
                            <button
                                class="mx-3 mt-2 w-[calc(100%-1.5rem)] rounded-xl bg-white py-3.5 text-center text-sm font-semibold text-sand-700 active:bg-sand-50 dark:bg-sand-800 dark:text-sand-200 dark:active:bg-sand-700"
                                @click="showSourcePicker = false"
                            >
                                {{ t('Cancel') }}
                            </button>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
