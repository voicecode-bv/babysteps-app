import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useTranslations() {
    const page = usePage();

    const translations = computed(() => (page.props.translations as Record<string, string>) ?? {});

    function t(key: string, replacements: Record<string, string | number> = {}): string {
        let value = translations.value[key] ?? key;

        for (const [placeholder, replacement] of Object.entries(replacements)) {
            value = value.replace(`:${placeholder}`, String(replacement));
        }

        return value;
    }

    return { t };
}
