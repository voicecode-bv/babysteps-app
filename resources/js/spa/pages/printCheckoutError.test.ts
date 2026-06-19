import { describe, expect, it } from 'vitest';
import { ApiError, NetworkError } from '@/spa/http/apiClient';
import { checkoutErrorContent } from './printCheckoutError';

describe('checkoutErrorContent', () => {
    it('treats a 422 as an order-review prompt, not a payment error', () => {
        const content = checkoutErrorContent(
            new ApiError(422, {}, 'One of the products is not available anymore.'),
        );

        expect(content.titleKey).toBe('Check your order');
        expect(content.bodyKey).not.toContain('payment');
    });

    it('shows a resolution-specific message for a too-low-resolution photo', () => {
        const content = checkoutErrorContent(
            new ApiError(
                422,
                { items: ['...'] },
                'One or more photos are not high enough resolution for the chosen size.',
                null,
                null,
                'photo_resolution_too_low',
            ),
        );

        expect(content.titleKey).toBe('Photo resolution too low');
        expect(content.bodyKey).toContain('resolution');
    });

    it('reports a network error as a connection problem', () => {
        expect(checkoutErrorContent(new NetworkError()).titleKey).toBe(
            'No connection',
        );
    });

    it('falls back to a payment error for a server failure', () => {
        const content = checkoutErrorContent(new ApiError(502, {}, 'boom'));

        expect(content.bodyKey).toBe(
            'Could not start the payment. Please try again.',
        );
    });

    it('falls back to a payment error for an unknown throwable', () => {
        expect(checkoutErrorContent(new Error('nope')).titleKey).toBe(
            'Something went wrong',
        );
    });
});
