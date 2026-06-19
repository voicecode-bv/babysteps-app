import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import { usePrintShopStore } from '@/spa/stores/printShop';
import type { PrintPhoto } from '@/spa/stores/printShop';

const apiGet = vi.fn();
const apiPost = vi.fn();

vi.mock('@/spa/http/externalApi', () => ({
    externalApi: {
        get: (path: string) => apiGet(path),
        post: (path: string, body: unknown) => apiPost(path, body),
    },
}));

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@nativephp/mobile', () => ({
    Browser: { open: vi.fn() },
    Dialog: { alert: vi.fn() },
    System: {},
}));

const PrintShop = (await import('./PrintShop.vue')).default;

const photo: PrintPhoto = {
    id: 'post-1:media-1',
    postId: 'post-1',
    mediaId: 'media-1',
    url: 'https://example.test/full.jpg',
    previewUrl: 'https://example.test/thumb.jpg',
    width: 1200,
    height: 800,
};

const catalogResponse = {
    data: [
        {
            id: 'offering-puzzle',
            app_product: 'puzzle',
            name: { 'en-EN': 'Jigsaw Puzzles' },
            price_minor: 1587,
            currency: 'EUR',
            min_photos: 1,
            max_photos: 1,
            user_options: [
                { attribute: 'Print Area', values: ['A', 'B'] },
            ],
            available: true,
            format: { width: 540, height: 400, orientation: 'auto' },
        },
    ],
    shipping_countries: ['NL', 'BE'],
    return_url: 'https://innerr.app',
    saved_address: null,
};

function mountPage() {
    const app = createApp(PrintShop);
    const host = document.createElement('div');
    document.body.appendChild(host);
    app.mount(host);

    return {
        host,
        unmount: () => {
            app.unmount();
            host.remove();
        },
    };
}

beforeEach(() => {
    document.body.innerHTML = '';
    apiGet.mockReset();
    apiPost.mockReset();
    const pinia = createPinia();
    setActivePinia(pinia);
});

describe('PrintShop product chooser', () => {
    it('keeps the chosen options when the selected product is tapped again', async () => {
        apiGet.mockResolvedValue(catalogResponse);
        apiPost.mockResolvedValue({ data: { price_minor: 1587 } });

        const store = usePrintShopStore();
        store.photos = [photo];

        const { host, unmount } = mountPage();
        await nextTick();
        await nextTick();

        // Open the puzzle category; with a single offering it is preselected.
        const categoryButton = Array.from(
            host.querySelectorAll('button'),
        ).find((button) => button.textContent?.includes('Photo puzzle'));
        expect(categoryButton).toBeTruthy();
        categoryButton!.click();
        await nextTick();

        // The option select for the preselected offering.
        const select = document.querySelector<HTMLSelectElement>('select');
        expect(select).toBeTruthy();

        select!.value = 'A';
        select!.dispatchEvent(new Event('change'));
        await nextTick();
        expect(store.selectedOfferingId).toBe('offering-puzzle');

        // Re-tap the already selected offering in the sheet.
        const offeringButton = Array.from(
            document.querySelectorAll<HTMLButtonElement>('button[aria-pressed]'),
        ).find((button) => button.getAttribute('aria-pressed') === 'true');
        expect(offeringButton).toBeTruthy();
        offeringButton!.click();
        await nextTick();

        // The chosen option must survive the re-tap.
        const selectAfter = document.querySelector<HTMLSelectElement>('select');
        expect(selectAfter!.value).toBe('A');

        unmount();
    });
});
