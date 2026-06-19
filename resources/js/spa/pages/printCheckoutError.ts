import { ApiError, NetworkError } from '@/spa/http/apiClient';

export interface CheckoutErrorContent {
    titleKey: string;
    bodyKey: string;
}

/**
 * Maps a failed checkout submission to an accurate dialog. A 422 means the
 * order itself was rejected (a product, photo, or detail is no longer valid),
 * not that the payment failed, so it must not show a payment error. Returns
 * translation keys; the caller localizes them.
 */
export function checkoutErrorContent(error: unknown): CheckoutErrorContent {
    if (error instanceof ApiError && error.status === 422) {
        // A photo is too low-resolution for the chosen size: tell the user
        // what to do instead of the generic "no longer available" message.
        if (error.code === 'photo_resolution_too_low') {
            return {
                titleKey: 'Photo resolution too low',
                bodyKey: 'One or more photos are too low resolution for the chosen size. Choose a smaller size or a sharper photo.',
            };
        }

        return {
            titleKey: 'Check your order',
            bodyKey: 'Something in your order is no longer available. Please review your items and try again.',
        };
    }

    if (error instanceof NetworkError) {
        return {
            titleKey: 'No connection',
            bodyKey: 'Check your internet connection and try again.',
        };
    }

    // Genuine payment or server failure (e.g. the payment provider rejected
    // the request).
    return {
        titleKey: 'Something went wrong',
        bodyKey: 'Could not start the payment. Please try again.',
    };
}
