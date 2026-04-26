import { onMounted, onUnmounted, reactive, ref, watch, type Ref } from 'vue';

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

interface Options {
    rootMargin?: string;
    immediate?: boolean;
}

export function useInfiniteScroll<T>(
    fetcher: (page: number) => Promise<PaginatedResponse<T>>,
    sentinelRef: Ref<HTMLElement | null>,
    options: Options = {},
) {
    const items = ref<T[]>([]) as Ref<T[]>;
    const page = ref(1);
    const lastPage = ref(1);
    const loading = ref(false);
    const error = ref<Error | null>(null);
    const finished = ref(false);

    let observer: IntersectionObserver | null = null;
    const seenIds = new Set<string | number>();

    async function loadMore(): Promise<void> {
        if (loading.value || finished.value) {
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await fetcher(page.value);

            for (const item of response.data) {
                const id = (item as { id?: string | number }).id;
                if (id !== undefined) {
                    if (seenIds.has(id)) continue;
                    seenIds.add(id);
                }
                items.value.push(item);
            }

            lastPage.value = response.meta.last_page;
            page.value = response.meta.current_page + 1;

            if (page.value > lastPage.value) {
                finished.value = true;
            }
        } catch (e) {
            error.value = e instanceof Error ? e : new Error(String(e));
        } finally {
            loading.value = false;
        }
    }

    async function reset(): Promise<void> {
        items.value = [];
        seenIds.clear();
        page.value = 1;
        lastPage.value = 1;
        finished.value = false;
        await loadMore();
    }

    function attachObserver(target: HTMLElement): void {
        observer?.disconnect();
        observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    loadMore();
                }
            },
            { rootMargin: options.rootMargin ?? '500px' },
        );
        observer.observe(target);
    }

    onMounted(() => {
        watch(
            sentinelRef,
            (element) => {
                if (element) {
                    attachObserver(element);
                } else {
                    observer?.disconnect();
                    observer = null;
                }
            },
            { immediate: true },
        );

        if (options.immediate !== false) {
            loadMore();
        }
    });

    onUnmounted(() => {
        observer?.disconnect();
        observer = null;
    });

    return reactive({
        items,
        page,
        lastPage,
        loading,
        error,
        finished,
        loadMore,
        reset,
    });
}
