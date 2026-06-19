import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useI18nStore } from '@/spa/stores/i18n';
import { usePrintTerms } from './usePrintTerms';

beforeEach(() => {
    setActivePinia(createPinia());
});

describe('usePrintTerms', () => {
    it('translates known Printdeal terms through the bundle', () => {
        useI18nStore().translations = {
            'Print Area': 'Formaat',
            'Box With Printed Sleeve': 'Doos met bedrukte wikkel',
        };

        const { printTerm } = usePrintTerms();

        expect(printTerm('Print Area')).toBe('Formaat');
        expect(printTerm('Box With Printed Sleeve')).toBe(
            'Doos met bedrukte wikkel',
        );
    });

    it('localizes the pcs unit inside size values', () => {
        useI18nStore().translations = { pcs: 'stukjes' };

        const { printTerm } = usePrintTerms();

        expect(printTerm('54 x 40 cm (500 pcs)')).toBe(
            '54 x 40 cm (500 stukjes)',
        );
    });

    it('matches a term despite different casing or spacing from the API', () => {
        useI18nStore().translations = {
            'Premium Thickness (4.5Cm)': 'Premium dikte (4,5 cm)',
            'Classic Thickness (2 Cm)': 'Klassieke dikte (2 cm)',
        };

        const { printTerm } = usePrintTerms();

        // Printdeal may send these with other casing/spacing than the key.
        expect(printTerm('Premium thickness (4.5 cm)')).toBe(
            'Premium dikte (4,5 cm)',
        );
        expect(printTerm('CLASSIC THICKNESS (2CM)')).toBe(
            'Klassieke dikte (2 cm)',
        );
    });

    it('falls back to the original for unknown terms', () => {
        const { printTerm } = usePrintTerms();

        expect(printTerm('Some New Attribute')).toBe('Some New Attribute');
    });
});
