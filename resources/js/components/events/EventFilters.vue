<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CityOption, EventVisualFilters } from '@/types/events';

const props = withDefaults(
    defineProps<{
        cities: CityOption[];
        modelValue: EventVisualFilters;
        layout?: 'horizontal' | 'sidebar';
    }>(),
    {
        layout: 'horizontal',
    },
);

const emit = defineEmits<{
    'update:modelValue': [EventVisualFilters];
    apply: [];
}>();

const form = reactive({
    date_from: props.modelValue.date_from ?? '',
    date_to: props.modelValue.date_to ?? '',
    city: props.modelValue.city,
    status: props.modelValue.status,
});

watch(
    () => props.modelValue,
    (value) => {
        form.date_from = value.date_from ?? '';
        form.date_to = value.date_to ?? '';
        form.city = value.city;
        form.status = value.status;
    },
);

function apply(): void {
    emit('update:modelValue', {
        date_from: form.date_from || null,
        date_to: form.date_to || null,
        city: form.city,
        status: form.status,
    });
    emit('apply');
}

const containerClass =
    props.layout === 'sidebar'
        ? 'flex flex-col gap-4'
        : 'flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4';
</script>

<template>
    <form :class="containerClass" @submit.prevent="apply">
        <div class="flex flex-col gap-1.5">
            <Label for="date_from">From</Label>
            <Input id="date_from" v-model="form.date_from" type="date" class="bg-background" />
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="date_to">To</Label>
            <Input id="date_to" v-model="form.date_to" type="date" class="bg-background" />
        </div>

        <div class="flex flex-col gap-1.5" :class="layout === 'sidebar' ? '' : 'min-w-56'">
            <Label>City</Label>
            <Select
                :model-value="form.city ?? 'all'"
                @update:model-value="(value) => (form.city = value === 'all' ? null : String(value))"
            >
                <SelectTrigger class="bg-background">
                    <SelectValue placeholder="All cities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All cities</SelectItem>
                    <SelectItem v-for="city in cities" :key="city.slug" :value="city.slug">
                        {{ city.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <Button type="submit" :class="layout === 'sidebar' ? 'w-full' : ''">Apply filters</Button>
    </form>
</template>
