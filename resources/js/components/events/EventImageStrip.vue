<script setup lang="ts">
import { computed, ref } from 'vue';
import type { EventVisualImage } from '@/types/events';

const props = defineProps<{
    images: EventVisualImage[];
    alt: string;
    variant?: 'hero' | 'strip';
}>();

const activeIndex = ref(0);

const sortedImages = computed(() => [...props.images].sort((a, b) => a.sort_order - b.sort_order));

function select(index: number): void {
    activeIndex.value = index;
}

function next(): void {
    if (sortedImages.value.length <= 1) return;
    activeIndex.value = (activeIndex.value + 1) % sortedImages.value.length;
}
</script>

<template>
    <div
        :class="
            variant === 'strip'
                ? 'flex gap-2'
                : 'group relative aspect-[16/10] overflow-hidden rounded-t-xl bg-muted'
        "
        @mouseenter="next"
    >
        <template v-if="variant === 'strip'">
            <img
                v-for="(image, index) in sortedImages"
                :key="image.url"
                :src="image.url"
                :alt="`${alt} image ${index + 1}`"
                class="h-20 w-28 rounded-md object-cover transition-transform duration-300 hover:scale-105"
            />
        </template>

        <template v-else>
            <img
                v-for="(image, index) in sortedImages"
                :key="image.url"
                :src="image.url"
                :alt="alt"
                class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500"
                :class="index === activeIndex ? 'opacity-100' : 'opacity-0'"
            />
            <div
                v-if="sortedImages.length > 1"
                class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
            >
                <button
                    v-for="(_, index) in sortedImages"
                    :key="index"
                    type="button"
                    class="h-2 w-2 rounded-full transition-all"
                    :class="index === activeIndex ? 'w-4 bg-white' : 'bg-white/50'"
                    @click.stop="select(index)"
                />
            </div>
        </template>
    </div>
</template>
