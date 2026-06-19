import { useTranslations } from '@/spa/composables/useTranslations';
import { useI18nStore } from '@/spa/stores/i18n';

/** Lowercase and strip all whitespace, so casing/spacing never blocks a match. */
function normalizeTerm(value: string): string {
    return value.toLowerCase().replace(/\s+/g, '');
}

// Normalized index of the active bundle, rebuilt when the bundle reference
// changes (i.e. on a locale switch). Module-level so it survives re-renders.
let indexedBundle: Record<string, string> | null = null;
let normalizedIndex: Record<string, string> = {};

function normalizedLookup(
    translations: Record<string, string>,
    raw: string,
): string | undefined {
    if (translations !== indexedBundle) {
        normalizedIndex = {};

        for (const [key, value] of Object.entries(translations)) {
            normalizedIndex[normalizeTerm(key)] = value;
        }

        indexedBundle = translations;
    }

    return normalizedIndex[normalizeTerm(raw)];
}

/**
 * Display-only localization of Printdeal vocabulary (attribute names like
 * 'Print Area', values like 'Box With Printed Sleeve'). The API only speaks
 * English; known terms translate through the lang bundles, sizes like
 * '54 x 40 cm (500 pcs)' get their unit localized, and anything unknown falls
 * back to the English original. The original strings are what gets sent to the
 * API; never feed translated terms back.
 *
 * Printdeal capitalizes and spaces its values inconsistently ('(2 Cm)' vs
 * '(4.5Cm)'), so an exact key miss retries against a case- and
 * whitespace-insensitive index before giving up.
 */
export function usePrintTerms() {
    const { t } = useTranslations();
    const i18n = useI18nStore();

    function printTerm(raw: string): string {
        const translated = t(raw);

        if (translated !== raw) {
            return translated;
        }

        const normalized = normalizedLookup(i18n.translations, raw);

        if (normalized !== undefined) {
            return normalized;
        }

        // '(500 pcs)' reads as '(500 stukjes)' / '(500 pièces)'.
        return raw.replace(
            /\((\d+)\s*pcs\)/,
            (_, count: string) => `(${count} ${t('pcs')})`,
        );
    }

    return { printTerm };
}
