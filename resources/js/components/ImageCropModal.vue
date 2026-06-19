<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import BottomSheet from '@/components/BottomSheet.vue';
import { readExif } from '@/composables/useExif';
import type { ExifData } from '@/composables/useExif';
import { useTranslations } from '@/spa/composables/useTranslations';
import 'vue-advanced-cropper/dist/style.css';

type Ratio = '1:1' | '5:4';

/** Crop rectangle in the source image's pixels, for archiving + re-cropping. */
export interface CropRect {
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        src: string | null;
        lockedRatio?: Ratio | null;
    }>(),
    {
        lockedRatio: null,
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (
        e: 'cropped',
        blob: Blob,
        dataUrl: string,
        exif: ExifData,
        crop: CropRect | null,
    ): void;
}>();

const { t } = useTranslations();

const cropperRef = useTemplateRef<InstanceType<typeof Cropper>>('cropperRef');
const ratio = ref<Ratio>(props.lockedRatio ?? '1:1');
const processing = ref(false);

const aspectRatio = computed<number>(() => (ratio.value === '1:1' ? 1 : 5 / 4));

const stencilProps = computed(() => ({
    aspectRatio: aspectRatio.value,
    movable: true,
    resizable: true,
}));

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            ratio.value = props.lockedRatio ?? '1:1';
            processing.value = false;
        }
    },
);

function close() {
    if (processing.value) {
        return;
    }

    emit('update:open', false);
}

function onSheetUpdate(value: boolean) {
    if (!value) {
        close();
    } else {
        emit('update:open', true);
    }
}

// Cap the cropped canvas by total area, not longest edge. The cropped blob
// becomes the stored original the canvas print is generated from, so it must
// keep as many pixels as possible: the old 2048px edge cap (~4 MP) left large
// prints below the 150 DPI floor. An area cap keeps both dimensions large for
// any aspect ratio while staying under WKWebView's ~16.7 MP (4096²) canvas
// limit, above which it silently renders blank. 12 MP matches a typical phone
// photo, so most crops now upload at full resolution.
const MAX_OUTPUT_PIXELS = 12_000_000;

async function confirm() {
    const instance = cropperRef.value;

    if (!instance || processing.value) {
        return;
    }

    const result = instance.getResult();
    const sourceCanvas = result.canvas;

    if (!sourceCanvas) {
        return;
    }

    // The crop rectangle in the source image's own pixels, so the uncropped
    // original archived server-side can be re-cropped to the same framing.
    const crop: CropRect | null = result.coordinates
        ? {
              x: Math.round(result.coordinates.left),
              y: Math.round(result.coordinates.top),
              width: Math.round(result.coordinates.width),
              height: Math.round(result.coordinates.height),
          }
        : null;

    const area = sourceCanvas.width * sourceCanvas.height;
    const scale =
        area > MAX_OUTPUT_PIXELS ? Math.sqrt(MAX_OUTPUT_PIXELS / area) : 1;

    let canvas: HTMLCanvasElement;

    if (scale < 1) {
        canvas = document.createElement('canvas');
        canvas.width = Math.round(sourceCanvas.width * scale);
        canvas.height = Math.round(sourceCanvas.height * scale);
        const ctx = canvas.getContext('2d');

        if (!ctx) {
            return;
        }

        ctx.drawImage(sourceCanvas, 0, 0, canvas.width, canvas.height);
    } else {
        canvas = sourceCanvas;
    }

    processing.value = true;

    // Read EXIF from the ORIGINAL source before encoding the cropped canvas —
    // canvas.toBlob() re-encodes JPEG and strips EXIF.
    const exif = props.src
        ? await readExif(props.src)
        : { taken_at: null, latitude: null, longitude: null };

    canvas.toBlob(
        (blob) => {
            if (!blob) {
                processing.value = false;

                return;
            }

            const dataUrl = URL.createObjectURL(blob);
            emit('cropped', blob, dataUrl, exif, crop);
        },
        'image/jpeg',
        // The cropped blob is archived as the print original, so favour
        // fidelity: 0.92 keeps JPEG artefacts off a large canvas print.
        0.92,
    );
}

const ratios: { value: Ratio; label: string }[] = [
    { value: '1:1', label: '1:1' },
    { value: '5:4', label: '5:4' },
];
</script>

<template>
    <BottomSheet :open="open" @update:open="onSheetUpdate">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-ink">
                    {{ t('Crop photo') }}
                </h2>
                <button
                    class="text-sand-500"
                    :aria-label="t('Close')"
                    @click="close"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="size-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </template>

        <div class="flex flex-col gap-4 px-4 py-4">
            <div v-if="lockedRatio === null" class="flex flex-wrap gap-2">
                <button
                    v-for="option in ratios"
                    :key="option.value"
                    class="rounded-full px-4 py-2 transition-colors"
                    :class="
                        ratio === option.value
                            ? 'bg-action text-white shadow-sm'
                            : 'bg-sand-100 text-sand-700'
                    "
                    @click="ratio = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <div class="overflow-hidden rounded-lg bg-black">
                <Cropper
                    v-if="src"
                    ref="cropperRef"
                    :src="src"
                    :stencil-props="stencilProps"
                    image-restriction="fit-area"
                    class="h-[55dvh] w-full"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex gap-3 px-4 py-3">
                <button
                    class="flex-1 rounded-lg bg-sand-100 py-3 font-semibold text-sand-700 transition-colors"
                    :disabled="processing"
                    @click="close"
                >
                    {{ t('Cancel') }}
                </button>
                <button
                    class="flex-1 rounded-lg bg-action py-3 font-semibold text-white shadow-sm transition-colors hover:bg-action-hover disabled:opacity-40"
                    :disabled="processing || !src"
                    @click="confirm"
                >
                    {{ processing ? t('Cropping...') : t('Crop') }}
                </button>
            </div>
        </template>
    </BottomSheet>
</template>
