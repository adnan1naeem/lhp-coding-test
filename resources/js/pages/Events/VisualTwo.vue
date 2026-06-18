<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AttendeeDialog from '@/components/events/AttendeeDialog.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventImageStrip from '@/components/events/EventImageStrip.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useEventVisualData } from '@/composables/useEventVisualData';
import { formatEventTimeDual, formatMonthLabel, monthGroupKey } from '@/lib/formatEventTime';
import type { CityOption, EventVisualFilters, EventVisualItem } from '@/types/events';

const props = defineProps<{
    cities: CityOption[];
    filters: EventVisualFilters;
}>();

const { events, filters, loading, hasLoadedOnce, hasMore, total, loadMore, updateFilters } =
    useEventVisualData(props.filters, 30);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const selectedEvent = ref<EventVisualItem | null>(null);
const dialogOpen = ref(false);

const groupedEvents = computed(() => {
    const groups = new Map<string, EventVisualItem[]>();

    for (const event of events.value) {
        const key = monthGroupKey(event.schedule.starts_at);
        const bucket = groups.get(key) ?? [];
        bucket.push(event);
        groups.set(key, bucket);
    }

    return [...groups.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, items]) => ({ key, label: formatMonthLabel(key), items }));
});

function openRegister(event: EventVisualItem): void {
    selectedEvent.value = event;
    dialogOpen.value = true;
}

function onRegistered(): void {
    if (selectedEvent.value) {
        selectedEvent.value.attendee_count += 1;
    }
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                void loadMore();
            }
        },
        { rootMargin: '400px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    void loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events Visual 2" />

    <div class="flex flex-col gap-6 p-4 md:p-6 lg:flex-row lg:items-start">
        <aside class="w-full shrink-0 lg:sticky lg:top-6 lg:w-72">
            <div class="mb-4">
                <h1 class="text-2xl font-semibold tracking-tight">Event Timeline</h1>
                <p class="text-sm text-muted-foreground">
                    {{
                        total !== null
                            ? `${total.toLocaleString()} published events`
                            : 'Chronological view'
                    }}
                </p>
            </div>

            <EventFilters
                :cities="cities"
                :model-value="filters"
                layout="sidebar"
                @apply="updateFilters(filters)"
            />
        </aside>

        <div class="min-w-0 flex-1">
            <div
                v-if="hasLoadedOnce && events.length === 0"
                class="rounded-xl border border-dashed p-12 text-center text-muted-foreground"
            >
                No events match your filters.
            </div>

            <div class="relative space-y-12">
                <div
                    class="absolute top-0 bottom-0 left-4 hidden w-px bg-border md:left-1/2 md:block md:-translate-x-1/2"
                />

                <section v-for="group in groupedEvents" :key="group.key" class="space-y-6">
                    <div class="relative flex justify-center">
                        <div
                            class="rounded-full border bg-background px-4 py-1 text-sm font-medium shadow-sm animate-in fade-in zoom-in-95"
                        >
                            {{ group.label }}
                        </div>
                    </div>

                    <article
                        v-for="(event, index) in group.items"
                        :key="event.id"
                        class="relative md:grid md:grid-cols-2 md:gap-8"
                    >
                        <div
                            class="hidden md:absolute md:top-6 md:left-1/2 md:z-10 md:block md:size-3 md:-translate-x-1/2 md:rounded-full md:bg-primary"
                        />

                        <div
                            :class="[
                                'md:col-span-1',
                                index % 2 === 0
                                    ? 'md:col-start-1 md:pr-10 animate-in fade-in slide-in-from-left-4'
                                    : 'md:col-start-2 md:pl-10 animate-in fade-in slide-in-from-right-4',
                            ]"
                        >
                            <div
                                class="rounded-xl border bg-card p-4 shadow-sm transition-shadow duration-300 hover:shadow-md"
                            >
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <Badge variant="secondary">{{ event.type }}</Badge>
                                    <Badge variant="outline">{{ event.status }}</Badge>
                                </div>

                                <EventImageStrip
                                    :images="event.images"
                                    :alt="event.title"
                                    variant="strip"
                                />

                                <h2 class="mt-4 text-lg font-semibold">{{ event.title }}</h2>
                                <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                    {{ event.description }}
                                </p>

                                <div class="mt-4 space-y-1 text-sm">
                                    <p>{{ event.location.display }}</p>
                                    <p>
                                        <span class="font-medium">Your time:</span>
                                        {{
                                            formatEventTimeDual(
                                                event.schedule.starts_at,
                                                event.location.timezone,
                                            ).local
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Event time:</span>
                                        {{
                                            formatEventTimeDual(
                                                event.schedule.starts_at,
                                                event.location.timezone,
                                            ).eventLocal
                                        }}
                                    </p>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <span class="text-xs text-muted-foreground">
                                        {{ event.attendee_count.toLocaleString() }} interested
                                    </span>
                                    <Button size="sm" @click="openRegister(event)">
                                        Register interest
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>

            <div ref="sentinel" class="h-8" />

            <div v-if="loading" class="flex justify-center py-4">
                <Spinner class="size-6" />
            </div>
        </div>
    </div>

    <AttendeeDialog
        :event="selectedEvent"
        :open="dialogOpen"
        @update:open="dialogOpen = $event"
        @registered="onRegistered"
    />
</template>
