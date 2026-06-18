<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AttendeeDialog from '@/components/events/AttendeeDialog.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventImageStrip from '@/components/events/EventImageStrip.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useEventVisualData } from '@/composables/useEventVisualData';
import { formatEventTimeDual } from '@/lib/formatEventTime';
import type { CityOption, EventVisualFilters, EventVisualItem } from '@/types/events';

const props = defineProps<{
    cities: CityOption[];
    filters: EventVisualFilters;
}>();

const { events, filters, loading, hasLoadedOnce, hasMore, total, loadMore, updateFilters } =
    useEventVisualData(props.filters, 24);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const selectedEvent = ref<EventVisualItem | null>(null);
const dialogOpen = ref(false);

const subtitle = computed(() =>
    total.value !== null ? `${total.value.toLocaleString()} published events` : 'Browse upcoming events',
);

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
    <Head title="Events Visual 1" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Event Visuals</h1>
            <p class="text-sm text-muted-foreground">{{ subtitle }}</p>
        </div>

        <EventFilters
            :cities="cities"
            :model-value="filters"
            layout="horizontal"
            @apply="updateFilters(filters)"
        />

        <div
            v-if="hasLoadedOnce && events.length === 0"
            class="rounded-xl border border-dashed p-12 text-center text-muted-foreground"
        >
            No events match your filters.
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="(event, index) in events"
                :key="event.id"
                class="overflow-hidden py-0 transition-shadow duration-300 hover:shadow-lg animate-in fade-in slide-in-from-bottom-4 fill-mode-both"
                :style="{ animationDelay: `${(index % 12) * 40}ms` }"
            >
                <EventImageStrip :images="event.images" :alt="event.title" variant="hero" />

                <CardHeader class="gap-3">
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="secondary">{{ event.type }}</Badge>
                        <Badge variant="outline">{{ event.status }}</Badge>
                    </div>
                    <CardTitle class="text-lg">{{ event.title }}</CardTitle>
                    <CardDescription class="line-clamp-3">{{ event.description }}</CardDescription>
                </CardHeader>

                <CardContent class="space-y-2 text-sm">
                    <p class="text-muted-foreground">{{ event.location.display }}</p>
                    <div class="space-y-1">
                        <p>
                            <span class="font-medium text-foreground">Your time:</span>
                            {{
                                formatEventTimeDual(event.schedule.starts_at, event.location.timezone)
                                    .local
                            }}
                        </p>
                        <p>
                            <span class="font-medium text-foreground">Event time:</span>
                            {{
                                formatEventTimeDual(event.schedule.starts_at, event.location.timezone)
                                    .eventLocal
                            }}
                        </p>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ event.attendee_count.toLocaleString() }} interested
                    </p>
                </CardContent>

                <CardFooter>
                    <Button class="w-full" @click="openRegister(event)">Register interest</Button>
                </CardFooter>
            </Card>
        </div>

        <div ref="sentinel" class="h-8" />

        <div v-if="loading" class="flex justify-center py-4">
            <Spinner class="size-6" />
        </div>
    </div>

    <AttendeeDialog
        :event="selectedEvent"
        :open="dialogOpen"
        @update:open="dialogOpen = $event"
        @registered="onRegistered"
    />
</template>
