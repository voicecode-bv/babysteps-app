<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Camera, On, Off, Events } from '@nativephp/mobile';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { store } from '@/actions/App/Http/Controllers/PostActionController';
import ImageCropModal from '@/components/ImageCropModal.vue';
import { type ExifData } from '@/composables/useExif';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import cameraIcon from '../../svg/doodle-icons/camera.svg';
import cropIcon from '../../svg/doodle-icons/crop.svg';
import photoIcon from '../../svg/doodle-icons/photo.svg';
import videoCameraIcon from '../../svg/doodle-icons/video-camera.svg';

function iconMaskStyle(url: string) {
    return {
        maskImage: `url(${url})`,
        WebkitMaskImage: `url(${url})`,
        maskSize: 'contain',
        WebkitMaskSize: 'contain',
        maskRepeat: 'no-repeat',
        WebkitMaskRepeat: 'no-repeat',
        maskPosition: 'center',
        WebkitMaskPosition: 'center',
    };
}

interface Circle {
    id: number;
    name: string;
    members_count: number;
}

const props = withDefaults(
    defineProps<{
        circles?: Circle[] | null;
        defaultCircleIds?: number[] | null;
    }>(),
    {
        circles: () => [],
        defaultCircleIds: () => [],
    },
);

const { t } = useTranslations();

const circles = computed<Circle[]>(() => props.circles ?? []);
const defaultCircleIds = computed<number[]>(() => props.defaultCircleIds ?? []);

// Pre-select default circles, but only those that exist in the available circles
const availableCircleIds = circles.value.map((c) => c.id);
const initialCircleIds = defaultCircleIds.value.filter((id) => availableCircleIds.includes(id));

const form = useForm({
    media_path: null as string | null,
    caption: '',
    circle_ids: initialCircleIds,
});

const mediaPreview = ref<string | null>(null);
const mediaIsVideo = ref(false);
const showSourcePicker = ref(false);
const showCropModal = ref(false);

const isValidForm = computed(() => form.media_path !== null && form.circle_ids.length > 0);

function openSourcePicker() {
    showSourcePicker.value = true;
}

async function selectFromGallery() {
    showSourcePicker.value = false;
    await Camera.pickImages().all();
}

async function openCamera() {
    showSourcePicker.value = false;
    await Camera.getPhoto();
}

async function recordVideo() {
    showSourcePicker.value = false;
    await Camera.recordVideo();
}

async function loadPreview(path: string): Promise<string | null> {
    try {
        const response = await fetch(`/native-media?path=${encodeURIComponent(path)}`);

        if (!response.ok) {
            return null;
        }

        const { data_url } = await response.json();

        return data_url;
    } catch {
        return null;
    }
}

async function handlePhotoTaken(payload: { path: string; mimeType: string }) {
    form.media_path = payload.path;
    mediaPreview.value = await loadPreview(payload.path);
    mediaIsVideo.value = false;
}

async function handleVideoRecorded(payload: { path: string; mimeType: string }) {
    form.media_path = payload.path;
    mediaPreview.value = await loadPreview(payload.path);
    mediaIsVideo.value = true;
}

async function handleMediaSelected(payload: { success: boolean; files: { path: string; mimeType: string }[]; cancelled: boolean }) {
    if (!payload.success || payload.cancelled || !payload.files.length) {
        return;
    }

    const file = payload.files[0];
    form.media_path = file.path;
    mediaPreview.value = await loadPreview(file.path);
    mediaIsVideo.value = file.mimeType?.startsWith('video/') ?? false;
}

function removeMedia() {
    form.media_path = null;
    mediaPreview.value = null;
    mediaIsVideo.value = false;
}

function openCropModal() {
    if (!mediaPreview.value || mediaIsVideo.value) {
        return;
    }

    showCropModal.value = true;
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));

    return match ? decodeURIComponent(match[3]) : null;
}

function arrayBufferToBase64(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    const chunkSize = 0x8000;

    for (let i = 0; i < bytes.length; i += chunkSize) {
        binary += String.fromCharCode.apply(null, Array.from(bytes.subarray(i, i + chunkSize)));
    }

    return btoa(binary);
}

async function handleCropped(blob: Blob, dataUrl: string, exif: ExifData) {
    const buffer = await blob.arrayBuffer();
    const base64 = arrayBufferToBase64(buffer);
    const xsrf = getCookie('XSRF-TOKEN');

    const response = await fetch('/posts/cropped-media', {
        method: 'POST',
        body: JSON.stringify({
            data: base64,
            taken_at: exif.taken_at,
            latitude: exif.latitude,
            longitude: exif.longitude,
        }),
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
        },
    });

    if (!response.ok) {
        return;
    }

    const { path } = (await response.json()) as { path: string };
    form.media_path = path;
    mediaPreview.value = dataUrl;
    showCropModal.value = false;
}

onMounted(() => {
    On(Events.Camera.PhotoTaken, handlePhotoTaken);
    On(Events.Camera.VideoRecorded, handleVideoRecorded);
    On(Events.Gallery.MediaSelected, handleMediaSelected);
});

onUnmounted(() => {
    Off(Events.Camera.PhotoTaken, handlePhotoTaken);
    Off(Events.Camera.VideoRecorded, handleVideoRecorded);
    Off(Events.Gallery.MediaSelected, handleMediaSelected);
});

const allCirclesSelected = computed(() => circles.value.length > 0 && form.circle_ids.length === circles.value.length);

function toggleAllCircles() {
    if (allCirclesSelected.value) {
        form.circle_ids = [];
    } else {
        form.circle_ids = circles.value.map((c) => c.id);
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
    form.post(store.url());
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
            <span />
        </template>

        <div class="relative mt-10 min-h-full pb-[calc(theme(spacing.40)+env(safe-area-inset-bottom))]">
            <div class="relative space-y-5 px-4 pt-4 pb-24">
                <!-- Media -->
                <section class="overflow-hidden rounded-lg bg-white/50 shadow-sm backdrop-blur-sm dark:bg-sand-800/60">
                    <div v-if="mediaPreview" class="relative">
                        <video v-if="mediaIsVideo" :src="mediaPreview" class="w-full object-cover" controls />
                        <img v-else :src="mediaPreview" class="w-full object-cover" :alt="t('Selected photo')" />
                        <button
                            v-if="!mediaIsVideo"
                            class="absolute left-3 top-3 flex size-9 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm"
                            :aria-label="t('Crop photo')"
                            @click="openCropModal"
                        >
                            <span aria-hidden="true" class="inline-block size-4 bg-current" :style="iconMaskStyle(cropIcon)"></span>
                        </button>
                        <button
                            class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm"
                            :aria-label="t('Cancel')"
                            @click="removeMedia"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <button v-else class="flex w-full flex-col items-center justify-center gap-3 px-8 py-14 active:bg-sand-100/40 dark:active:bg-sand-800/40" @click="openSourcePicker">
                        <div class="flex size-20 items-center justify-center rounded-2xl bg-sage-100 text-teal dark:bg-sage-900/40">
                            <span aria-hidden="true" class="inline-block size-10 bg-current" :style="iconMaskStyle(cameraIcon)"></span>
                        </div>
                        <span class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('Add a photo') }}</span>
                    </button>

                    <p v-if="form.errors.media_path" class="px-5 pb-4 text-xs text-blush-500">{{ form.errors.media_path }}</p>
                </section>

                <!-- Caption -->
                <section class="rounded-lg bg-white/50 p-5 shadow-sm backdrop-blur-sm dark:bg-sand-800/60">
                    <label for="post-caption" class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">
                        {{ t('Caption') }}
                    </label>
                    <textarea
                        id="post-caption"
                        v-model="form.caption"
                        :placeholder="t('Write a caption...')"
                        rows="3"
                        maxlength="2200"
                        class="mt-2 w-full resize-none border-0 bg-transparent p-0 text-base text-sand-800 placeholder-sand-400 focus:outline-none focus:ring-0 dark:text-sand-100 dark:placeholder-sand-500"
                    />
                    <p v-if="form.errors.caption" class="mt-1 text-xs text-blush-500">{{ form.errors.caption }}</p>
                </section>

                <!-- Circle selection -->
                <section v-if="circles.length > 0" class="rounded-lg bg-white/50 p-5 shadow-sm backdrop-blur-sm dark:bg-sand-800/60">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ t('Share with circles') }}</p>
                        <button
                            class="text-xs font-medium text-teal hover:text-teal-light"
                            @click="toggleAllCircles"
                        >
                            {{ allCirclesSelected ? t('Deselect all') : t('Select all') }}
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="circle in circles"
                            :key="circle.id"
                            class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                            :class="form.circle_ids.includes(circle.id)
                                ? 'bg-teal text-white shadow-sm'
                                : 'bg-white/70 text-sand-700 shadow-sm hover:bg-white dark:bg-sand-800/70 dark:text-sand-200'"
                            @click="toggleCircle(circle.id)"
                        >
                            {{ circle.name }}
                        </button>
                    </div>
                    <p v-if="form.errors.circle_ids" class="mt-2 text-xs text-blush-500">{{ form.errors.circle_ids }}</p>
                </section>

                <!-- Upload progress -->
                <section v-if="form.progress" class="rounded-lg bg-white/50 p-5 shadow-sm backdrop-blur-sm dark:bg-sand-800/60">
                    <div class="h-1.5 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-700">
                        <div
                            class="h-full rounded-full bg-teal transition-all duration-300"
                            :style="{ width: `${form.progress.percentage}%` }"
                        />
                    </div>
                    <p class="mt-2 text-center text-xs font-medium text-sand-500 dark:text-sand-400">
                        {{ form.progress.percentage }}%
                    </p>
                </section>

                <!-- Share -->
                <button
                    :disabled="!isValidForm || form.processing"
                    class="w-full rounded-full bg-teal py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-light disabled:opacity-40"
                    @click="submit"
                >
                    {{ form.processing ? t('Sharing...') : t('Share') }}
                </button>
            </div>
        </div>

        <!-- Source Picker Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showSourcePicker" class="fixed inset-0 z-50 flex items-center justify-center px-6 bg-black/40" @click.self="showSourcePicker = false">
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="scale-95 opacity-0"
                        enter-to-class="scale-100 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="scale-100 opacity-100"
                        leave-to-class="scale-95 opacity-0"
                    >
                        <div v-if="showSourcePicker" class="w-full max-w-sm overflow-hidden rounded-lg bg-white dark:bg-sand-800">
                            <button
                                class="flex w-full items-center gap-3 px-5 py-4 text-left text-sm font-medium text-sand-700 active:bg-sand-50 dark:text-sand-200 dark:active:bg-sand-700"
                                @click="openCamera"
                            >
                                <span aria-hidden="true" class="inline-block size-5 bg-sand-500 dark:bg-sand-400" :style="iconMaskStyle(cameraIcon)"></span>
                                {{ t('Take a photo') }}
                            </button>
                            <div class="mx-5 border-t border-sand-100 dark:border-sand-700" />
                            <button
                                class="flex w-full items-center gap-3 px-5 py-4 text-left text-sm font-medium text-sand-700 active:bg-sand-50 dark:text-sand-200 dark:active:bg-sand-700"
                                @click="recordVideo"
                            >
                                <span aria-hidden="true" class="inline-block size-5 bg-sand-500 dark:bg-sand-400" :style="iconMaskStyle(videoCameraIcon)"></span>
                                {{ t('Record a video') }}
                            </button>
                            <div class="mx-5 border-t border-sand-100 dark:border-sand-700" />
                            <button
                                class="flex w-full items-center gap-3 px-5 py-4 text-left text-sm font-medium text-sand-700 active:bg-sand-50 dark:text-sand-200 dark:active:bg-sand-700"
                                @click="selectFromGallery"
                            >
                                <span aria-hidden="true" class="inline-block size-5 bg-sand-500 dark:bg-sand-400" :style="iconMaskStyle(photoIcon)"></span>
                                {{ t('Choose from gallery') }}
                            </button>
                            <div class="border-t border-sand-100 dark:border-sand-700" />
                            <button
                                class="w-full py-3.5 text-center text-sm font-semibold text-sand-500 active:bg-sand-50 dark:text-sand-400 dark:active:bg-sand-700"
                                @click="showSourcePicker = false"
                            >
                                {{ t('Cancel') }}
                            </button>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <ImageCropModal
            :open="showCropModal"
            :src="mediaPreview"
            @update:open="showCropModal = $event"
            @cropped="handleCropped"
        />
    </AppLayout>
</template>
