import { computed, reactive, ref } from 'vue';
import type { EventVisualFilters, EventVisualItem } from '@/types/events';

interface VisualDataResponse {
    data: EventVisualItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    stats: { ms: number; bytes: number };
}

export function useEventVisualData(initialFilters: EventVisualFilters, perPage = 24) {
    const filters = reactive<EventVisualFilters>({ ...initialFilters });
    const events = ref<EventVisualItem[]>([]);
    const page = ref(0);
    const lastPage = ref<number | null>(null);
    const total = ref<number | null>(null);
    const loading = ref(false);
    const hasLoadedOnce = ref(false);

    const hasMore = computed(() => lastPage.value === null || page.value < lastPage.value);

    function buildParams(nextPage: number): URLSearchParams {
        const params = new URLSearchParams({
            page: String(nextPage),
            per_page: String(perPage),
        });

        if (filters.status) params.set('status', filters.status);
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.city) params.set('city', filters.city);

        return params;
    }

    async function loadMore(): Promise<void> {
        if (loading.value || !hasMore.value) {
            return;
        }

        loading.value = true;

        try {
            const response = await fetch(`/events/visual-data?${buildParams(page.value + 1).toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Failed to load events');
            }

            const payload = (await response.json()) as VisualDataResponse;
            events.value.push(...payload.data);
            page.value = payload.current_page;
            lastPage.value = payload.last_page;
            total.value = payload.total;
            hasLoadedOnce.value = true;
        } finally {
            loading.value = false;
        }
    }

    function resetAndLoad(): void {
        events.value = [];
        page.value = 0;
        lastPage.value = null;
        total.value = null;
        hasLoadedOnce.value = false;
        void loadMore();
    }

    function updateFilters(next: Partial<EventVisualFilters>): void {
        Object.assign(filters, next);
        resetAndLoad();
    }

    return {
        filters,
        events,
        loading,
        hasLoadedOnce,
        hasMore,
        total,
        loadMore,
        resetAndLoad,
        updateFilters,
    };
}
