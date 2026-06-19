import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { PostData } from '@/spa/components/PostCard.vue';

const apiGet = vi.fn();
const apiPost = vi.fn();

vi.mock('@/spa/http/externalApi', () => ({
    externalApi: {
        get: (path: string) => apiGet(path),
        post: (path: string, body: unknown) => apiPost(path, body),
    },
}));

const {
    printablePhotos,
    usePrintShopStore,
    printDimensionsMm,
    targetPrintDpi,
    isLowResolutionForPrint,
    sortSizeOptionValues,
    parseSizeValue,
    isSizeOption,
    parseThicknessCm,
    isThicknessOption,
    sortThicknessOptionValues,
    trimShippingAddress,
} = await import('./printShop');
type PrintOffering = import('./printShop').PrintOffering;
type PrintPhoto = import('./printShop').PrintPhoto;

function makePost(overrides: Partial<PostData> = {}): PostData {
    return {
        id: 'post-1',
        type: 'media',
        media_url: 'https://cdn.test/full.jpg',
        media_type: 'image',
        thumbnail_url: 'https://cdn.test/thumb.jpg',
        thumbnail_small_url: null,
        media_status: 'ready',
        width: 1200,
        height: 900,
        media: [
            {
                id: 'm1',
                url: 'https://cdn.test/full.jpg',
                type: 'image',
                status: 'ready',
                thumbnail_url: 'https://cdn.test/thumb.jpg',
            },
        ],
        caption: null,
        location: null,
        created_at: '2026-06-01T10:00:00Z',
        user: { id: 'user-1', name: 'Test', username: 'test', avatar: null },
        is_liked: false,
        likes_count: 0,
        comments_count: 0,
        ...overrides,
    };
}

function catalogResponse() {
    return {
        data: [
            {
                id: 'offering-album',
                app_product: 'album',
                name: { 'nl-NL': 'Fotoalbum', 'en-EN': 'Photo album' },
                price_minor: 2495,
                currency: 'EUR',
                min_photos: 1,
                max_photos: 50,
                user_options: [],
                available: true,
                format: { width: 210, height: 210, orientation: 'fixed' },
            },
            {
                id: 'offering-basic-tee',
                app_product: 'tshirt',
                name: { 'en-EN': 'Basic T-shirt' },
                price_minor: 1995,
                currency: 'EUR',
                min_photos: 1,
                max_photos: 1,
                user_options: [{ attribute: 'Size', values: ['S', 'M', 'L'] }],
                available: true,
                format: { width: 210, height: 297, orientation: 'fixed' },
            },
            {
                id: 'offering-premium-tee',
                app_product: 'tshirt',
                name: { 'en-EN': 'Premium T-shirt' },
                price_minor: 2995,
                currency: 'EUR',
                min_photos: 1,
                max_photos: 1,
                user_options: [{ attribute: 'Size', values: ['S', 'M', 'L'] }],
                available: false,
                format: { width: 210, height: 297, orientation: 'fixed' },
            },
        ],
        shipping_countries: ['NL', 'BE'],
        return_url: 'https://innerr.app/print',
    };
}

async function makeLoadedStore() {
    apiGet.mockResolvedValue(catalogResponse());
    const store = usePrintShopStore();
    await store.ensureCatalog();

    return store;
}

beforeEach(() => {
    setActivePinia(createPinia());
    apiGet.mockReset();
    apiPost.mockReset();
});

describe('printablePhotos', () => {
    it('maps ready images with their media ids', function () {
        const photos = printablePhotos(makePost());

        expect(photos).toHaveLength(1);
        expect(photos[0]).toMatchObject({
            id: 'post-1:m1',
            postId: 'post-1',
            mediaId: 'm1',
            previewUrl: 'https://cdn.test/thumb.jpg',
        });
    });

    it('keeps only ready images and excludes quotes and empty posts', () => {
        const photos = printablePhotos(
            makePost({
                media: [
                    {
                        id: 'm1',
                        url: 'https://cdn.test/1.jpg',
                        type: 'image',
                        status: 'ready',
                    },
                    {
                        id: 'm2',
                        url: 'https://cdn.test/2.mp4',
                        type: 'video',
                        status: 'ready',
                    },
                    {
                        id: 'm3',
                        url: 'https://cdn.test/3.jpg',
                        type: 'image',
                        status: 'processing',
                    },
                ],
            }),
        );

        expect(photos.map((photo) => photo.id)).toEqual(['post-1:m1']);
        expect(printablePhotos(makePost({ type: 'quote' }))).toEqual([]);
        expect(printablePhotos(makePost({ media: [] }))).toEqual([]);
    });
});

describe('print shop store', () => {
    it('loads offerings, including multiple per app product', async () => {
        const store = await makeLoadedStore();

        expect(apiGet).toHaveBeenCalledWith('/print/products');
        expect(store.offerings).toHaveLength(3);
        expect(
            store.offerings.filter((o) => o.appProduct === 'tshirt'),
        ).toHaveLength(2);
        expect(store.shippingCountries).toEqual(['NL', 'BE']);
        expect(store.returnUrl).toBe('https://innerr.app/print');
    });

    it('maps the artwork format for each offering', async () => {
        const store = await makeLoadedStore();

        expect(
            store.offerings.find((o) => o.id === 'offering-album')?.format,
        ).toEqual({ width: 210, height: 210, orientation: 'fixed' });
    });

    it('refreshes an already loaded catalog in the background', async () => {
        const store = await makeLoadedStore();

        // Admin made the premium tee orderable since the first load.
        const updated = catalogResponse();
        updated.data[2].available = true;
        apiGet.mockResolvedValue(updated);

        await store.ensureCatalog();

        expect(apiGet).toHaveBeenCalledTimes(2);
        expect(
            store.offerings.find((o) => o.id === 'offering-premium-tee')
                ?.available,
        ).toBe(true);
    });

    it('only lets selectable offerings be selected', async () => {
        const store = await makeLoadedStore();
        store.setPhotosFromPosts([makePost(), makePost({ id: 'post-2' })]);

        // Two photos: t-shirts (max 1) do not qualify.
        store.selectOffering('offering-basic-tee');
        expect(store.selectedOfferingId).toBeNull();

        store.selectOffering('offering-album');
        expect(store.selectedOfferingId).toBe('offering-album');

        // Unavailable offerings never qualify.
        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-premium-tee');
        expect(store.selectedOfferingId).toBeNull();
    });

    it('collects multiple products in the cart without losing earlier ones', async () => {
        const store = await makeLoadedStore();

        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-album');
        store.addToCart('Fotoalbum', {});

        expect(store.cart).toHaveLength(1);
        // The pick is consumed; the next product starts fresh.
        expect(store.photoCount).toBe(0);
        expect(store.selectedOfferingId).toBeNull();

        store.setPhotosFromPosts([makePost({ id: 'post-2' })]);
        store.selectOffering('offering-basic-tee');
        store.addToCart('Basic T-shirt', { Size: 'M' });

        expect(store.cart).toHaveLength(2);
        expect(store.cart[0].name).toBe('Fotoalbum');
        expect(store.cart[1].options).toEqual({ Size: 'M' });
        expect(store.cartTotalMinor).toBe(2495 + 1995);

        store.removeCartItem(store.cart[0].id);
        expect(store.cart).toHaveLength(1);
        expect(store.cartTotalMinor).toBe(1995);
    });

    it('keeps the cart when a new photo pick starts', async () => {
        const store = await makeLoadedStore();
        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-album');
        store.addToCart('Fotoalbum', {});

        store.setPhotosFromPosts([makePost({ id: 'post-2' })]);
        store.resetPick();

        expect(store.cart).toHaveLength(1);
        expect(store.photoCount).toBe(0);
    });

    it('quotes option-dependent prices and uses them in the cart', async () => {
        apiPost.mockResolvedValue({ data: { price_minor: 3300 } });

        const store = await makeLoadedStore();
        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-basic-tee');

        const quoted = await store.quotePrice('offering-basic-tee', {
            Size: 'L',
        });
        store.addToCart('Basic T-shirt', { Size: 'L' }, quoted);

        expect(apiPost).toHaveBeenCalledWith('/print/quote', {
            offering_id: 'offering-basic-tee',
            options: { Size: 'L' },
        });
        expect(store.cart[0].priceMinor).toBe(3300);
        expect(store.cartTotalMinor).toBe(3300);
    });

    it('submits the whole cart as one order and clears it', async () => {
        apiPost.mockResolvedValue({
            data: { id: 'order-1', status: 'pending_payment', items: [] },
            checkout_url: 'https://mollie.test/checkout/abc',
        });

        const store = await makeLoadedStore();
        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-album');
        store.addToCart('Fotoalbum', {});
        store.setPhotosFromPosts([makePost({ id: 'post-2' })]);
        store.selectOffering('offering-basic-tee');
        store.addToCart('Basic T-shirt', { Size: 'M' });

        const checkoutUrl = await store.submitOrder({
            firstName: 'Michael',
            lastName: 'Blijleven',
            street: 'Hoofdstraat',
            houseNumber: '1',
            postalCode: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        });

        expect(checkoutUrl).toBe('https://mollie.test/checkout/abc');
        expect(store.placedOrder?.id).toBe('order-1');
        expect(store.cart).toHaveLength(0);
        // Snapshotted before the cart cleared, so the confirmation screen keeps
        // its mockups (photos + format) the API summary does not return.
        expect(store.placedItems).toHaveLength(2);
        expect(store.placedItems[0].photos).toHaveLength(1);
        expect(store.placedItems[0].format).toEqual({
            width: 210,
            height: 210,
            orientation: 'fixed',
        });
        expect(apiPost).toHaveBeenCalledWith(
            '/print/orders',
            expect.objectContaining({
                items: [
                    expect.objectContaining({
                        offering_id: 'offering-album',
                        photos: [{ post_id: 'post-1', media_id: 'm1' }],
                        options: undefined,
                    }),
                    expect.objectContaining({
                        offering_id: 'offering-basic-tee',
                        options: { Size: 'M' },
                    }),
                ],
                redirect_url: 'https://innerr.app/print',
            }),
        );
    });

    it('exposes a saved address from the catalog for prefilling', async () => {
        const response = catalogResponse();
        const savedAddress = {
            firstName: 'Michael',
            lastName: 'Blijleven',
            street: 'Hoofdstraat',
            houseNumber: '1',
            postalCode: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        };
        apiGet.mockResolvedValue({ ...response, saved_address: savedAddress });

        const store = usePrintShopStore();
        await store.ensureCatalog();

        expect(store.savedAddress).toEqual(savedAddress);
    });

    it('forwards the save-address opt-in and remembers it locally', async () => {
        apiPost.mockResolvedValue({
            data: { id: 'order-1', status: 'pending_payment', items: [] },
            checkout_url: 'https://mollie.test/checkout/abc',
        });

        const store = await makeLoadedStore();
        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-album');
        store.addToCart('Fotoalbum', {});

        const address = {
            firstName: 'Michael',
            lastName: 'Blijleven',
            street: 'Hoofdstraat',
            houseNumber: '1',
            postalCode: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        };
        await store.submitOrder(address, true);

        expect(apiPost).toHaveBeenCalledWith(
            '/print/orders',
            expect.objectContaining({ save_address: true }),
        );
        expect(store.savedAddress).toEqual(address);
    });

    it('defaults to not saving the address', async () => {
        apiPost.mockResolvedValue({
            data: { id: 'order-1', status: 'pending_payment', items: [] },
            checkout_url: 'https://mollie.test/checkout/abc',
        });

        const store = await makeLoadedStore();
        store.setPhotosFromPosts([makePost()]);
        store.selectOffering('offering-album');
        store.addToCart('Fotoalbum', {});

        await store.submitOrder({
            firstName: 'Michael',
            lastName: 'Blijleven',
            street: 'Hoofdstraat',
            houseNumber: '1',
            postalCode: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        });

        expect(apiPost).toHaveBeenCalledWith(
            '/print/orders',
            expect.objectContaining({ save_address: false }),
        );
        expect(store.savedAddress).toBeNull();
    });
});

function makeOffering(overrides: Partial<PrintOffering> = {}): PrintOffering {
    return {
        id: 'offering-1',
        appProduct: 'puzzle',
        name: null,
        priceMinor: 3495,
        currency: 'EUR',
        minPhotos: 1,
        maxPhotos: 1,
        userOptions: [],
        available: true,
        format: { width: 380, height: 280, orientation: 'auto' },
        artwork: null,
        ...overrides,
    };
}

function makePhoto(width: number | null, height: number | null): PrintPhoto {
    return {
        id: 'post-1:m1',
        postId: 'post-1',
        mediaId: 'm1',
        url: 'https://cdn.test/full.jpg',
        previewUrl: 'https://cdn.test/thumb.jpg',
        width,
        height,
    };
}

describe('printDimensionsMm', () => {
    it('resolves the chosen puzzle size to the typed mm', () => {
        const offering = makeOffering({
            artwork: {
                sizeAttribute: 'Formaat',
                sizes: [{ value: '90 x 60 cm', width: 906, height: 606 }],
                frameAttribute: null,
                frames: [],
            },
        });

        expect(printDimensionsMm(offering, { Formaat: '90 x 60 cm' })).toEqual({
            width: 906,
            height: 606,
        });
    });

    it('adds twice the canvas frame depth on top of the base size', () => {
        const offering = makeOffering({
            appProduct: 'canvas',
            artwork: {
                sizeAttribute: 'Formaat',
                sizes: [{ value: '20 x 20 cm', width: 200, height: 200 }],
                frameAttribute: 'Frame',
                frames: [
                    { value: '2 cm', depth: 20 },
                    { value: '4,5 cm', depth: 45 },
                ],
            },
        });

        expect(
            printDimensionsMm(offering, {
                Formaat: '20 x 20 cm',
                Frame: '4,5 cm',
            }),
        ).toEqual({ width: 290, height: 290 });
    });

    it('returns null until an artwork size is chosen', () => {
        const offering = makeOffering({
            artwork: {
                sizeAttribute: 'Formaat',
                sizes: [{ value: '90 x 60 cm', width: 906, height: 606 }],
                frameAttribute: null,
                frames: [],
            },
        });

        expect(printDimensionsMm(offering, {})).toBeNull();
    });

    it('falls back to the trim format without artwork config', () => {
        const offering = makeOffering({ artwork: null });

        expect(printDimensionsMm(offering, {})).toEqual({
            width: 380,
            height: 280,
        });
    });
});

describe('targetPrintDpi', () => {
    it('asks full 300 DPI for small prints and 150 DPI for large formats', () => {
        expect(targetPrintDpi(240)).toBe(300);
        expect(targetPrintDpi(906)).toBe(150);
        // Eases in between (50 x 70 cm puzzle, longest edge 706 mm).
        expect(targetPrintDpi(706)).toBeGreaterThan(150);
        expect(targetPrintDpi(706)).toBeLessThan(300);
    });
});

describe('isLowResolutionForPrint', () => {
    const puzzle = makeOffering({
        artwork: {
            sizeAttribute: 'Formaat',
            sizes: [
                { value: '90 x 60 cm', width: 906, height: 606 },
                { value: '30 x 20 cm', width: 306, height: 206 },
            ],
            frameAttribute: null,
            frames: [],
        },
    });

    it('warns for a 12 MP photo on a large puzzle', () => {
        expect(
            isLowResolutionForPrint(puzzle, { Formaat: '90 x 60 cm' }, [
                makePhoto(4032, 3024),
            ]),
        ).toBe(true);
    });

    it('does not warn for the same photo on a small puzzle', () => {
        expect(
            isLowResolutionForPrint(puzzle, { Formaat: '30 x 20 cm' }, [
                makePhoto(4032, 3024),
            ]),
        ).toBe(false);
    });

    it('does not warn before a size is chosen', () => {
        expect(
            isLowResolutionForPrint(puzzle, {}, [makePhoto(800, 600)]),
        ).toBe(false);
    });

    it('never warns on photos without known dimensions', () => {
        expect(
            isLowResolutionForPrint(puzzle, { Formaat: '90 x 60 cm' }, [
                makePhoto(null, null),
            ]),
        ).toBe(false);
    });
});

describe('sortSizeOptionValues', () => {
    it('orders dimension values from small to large by area', () => {
        expect(
            sortSizeOptionValues(['90 x 60 cm', '30 x 20 cm', '50 x 70 cm']),
        ).toEqual(['30 x 20 cm', '50 x 70 cm', '90 x 60 cm']);
    });

    it('parses a piece count suffix and decimal commas', () => {
        expect(
            sortSizeOptionValues([
                '68 x 44 cm (1000 pcs)',
                '28 x 19 cm (35 pcs)',
            ]),
        ).toEqual(['28 x 19 cm (35 pcs)', '68 x 44 cm (1000 pcs)']);
    });

    it('keeps non-dimension values in their original order, last', () => {
        expect(
            sortSizeOptionValues(['Glossy', '20 x 20 cm', 'Matte']),
        ).toEqual(['20 x 20 cm', 'Glossy', 'Matte']);
    });
});

describe('parseSizeValue', () => {
    it('reads width and height with the first number as width', () => {
        expect(parseSizeValue('60 x 40 cm')).toEqual({ width: 60, height: 40 });
        expect(parseSizeValue('20 X 20 cm')).toEqual({ width: 20, height: 20 });
    });

    it('ignores a piece-count suffix and accepts decimal commas', () => {
        expect(parseSizeValue('54 x 40 cm (500 pcs)')).toEqual({
            width: 54,
            height: 40,
        });
        expect(parseSizeValue('4,5 x 2 cm')).toEqual({ width: 4.5, height: 2 });
    });

    it('returns null for non-size values', () => {
        expect(parseSizeValue('Glossy')).toBeNull();
        expect(parseSizeValue('Classic Thickness (2 Cm)')).toBeNull();
    });
});

describe('isSizeOption', () => {
    it('is true only when every value is a size', () => {
        expect(isSizeOption(['20 x 20 cm', '60 x 40 cm'])).toBe(true);
        expect(isSizeOption(['60 x 40 cm', 'Glossy'])).toBe(false);
        expect(isSizeOption([])).toBe(false);
    });
});

describe('frame thickness helpers', () => {
    it('parses the cm depth from a thickness value', () => {
        expect(parseThicknessCm('Classic Thickness (2 Cm)')).toBe(2);
        expect(parseThicknessCm('Premium Thickness (4.5Cm)')).toBe(4.5);
    });

    it('does not treat a size as a thickness', () => {
        expect(parseThicknessCm('60 x 40 cm')).toBeNull();
        expect(isThicknessOption(['60 x 40 cm', '20 x 20 cm'])).toBe(false);
    });

    it('detects a frame thickness option and orders it thinnest first', () => {
        const values = ['Premium Thickness (4.5Cm)', 'Classic Thickness (2 Cm)'];

        expect(isThicknessOption(values)).toBe(true);
        expect(sortThicknessOptionValues(values)).toEqual([
            'Classic Thickness (2 Cm)',
            'Premium Thickness (4.5Cm)',
        ]);
    });
});

describe('artwork most-chosen mapping', () => {
    it('maps the popular size value from the catalog artwork config', async () => {
        const response = catalogResponse();
        (response.data[0] as Record<string, unknown>).artwork = {
            size_attribute: 'Formaat',
            sizes: [{ value: '60 x 40 cm', width: 600, height: 400 }],
            frame_attribute: null,
            frames: [],
            popular: '60 x 40 cm',
        };
        apiGet.mockResolvedValue(response);

        const store = usePrintShopStore();
        await store.ensureCatalog();

        expect(
            store.offerings.find((o) => o.id === 'offering-album')?.artwork
                ?.popularValue,
        ).toBe('60 x 40 cm');
    });
});

describe('trimShippingAddress', () => {
    const base = {
        firstName: ' Michael ',
        lastName: 'Blijleven',
        street: 'Hoofdstraat',
        houseNumber: '9',
        postalCode: '3121XJ',
        city: 'Schiedam',
        country: 'NL',
    };

    it('trims fields and drops an empty addition', () => {
        expect(trimShippingAddress({ ...base, houseNumberAddition: '  ' }))
            .toEqual({
                firstName: 'Michael',
                lastName: 'Blijleven',
                street: 'Hoofdstraat',
                houseNumber: '9',
                houseNumberAddition: undefined,
                postalCode: '3121XJ',
                city: 'Schiedam',
                country: 'NL',
            });
    });

    it('does not throw when the addition is undefined (second-order prefill)', () => {
        expect(() =>
            trimShippingAddress({ ...base, houseNumberAddition: undefined }),
        ).not.toThrow();
        expect(
            trimShippingAddress({ ...base, houseNumberAddition: undefined })
                .houseNumberAddition,
        ).toBeUndefined();
    });

    it('keeps a real addition', () => {
        expect(
            trimShippingAddress({ ...base, houseNumberAddition: ' B ' })
                .houseNumberAddition,
        ).toBe('B');
    });
});
