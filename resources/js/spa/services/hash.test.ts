import { describe, expect, it } from 'vitest';
import { sha256Hex } from './hash';

describe('sha256Hex', () => {
    it('matches the known SHA-256 vector for "abc"', async () => {
        expect(await sha256Hex('abc')).toBe(
            'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
        );
    });

    it('is deterministic for the same input', async () => {
        const id = '0b3c1d2e-4f56-7890-abcd-ef0123456789';

        expect(await sha256Hex(id)).toBe(await sha256Hex(id));
    });

    it('does not echo the raw value back', async () => {
        const id = 'user-123';

        expect(await sha256Hex(id)).not.toContain(id);
    });
});
