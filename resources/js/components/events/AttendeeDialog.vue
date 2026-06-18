<script setup lang="ts">
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EventVisualItem } from '@/types/events';

function csrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const props = defineProps<{
    event: EventVisualItem | null;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [boolean];
    registered: [];
}>();

const form = reactive({
    name: '',
    email: '',
});

const submitting = ref(false);

async function submit(): Promise<void> {
    if (!props.event) return;

    submitting.value = true;

    try {
        const response = await fetch(`/events/${props.event.id}/attendees`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(form),
        });

        const payload = await response.json();

        if (!response.ok) {
            const message = payload.message ?? payload.errors?.email?.[0] ?? 'Registration failed.';
            toast.error(message);
            return;
        }

        toast.success(payload.message ?? 'You are on the list!');
        form.name = '';
        form.email = '';
        emit('registered');
        emit('update:open', false);
    } catch {
        toast.error('Something went wrong. Please try again.');
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Register interest</DialogTitle>
                <DialogDescription>
                    Join the attendee list for
                    <span class="font-medium text-foreground">{{ event?.title }}</span
                    >. We will email you a confirmation.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="attendee-name">Name</Label>
                    <Input id="attendee-name" v-model="form.name" required autocomplete="name" />
                </div>
                <div class="grid gap-2">
                    <Label for="attendee-email">Email</Label>
                    <Input
                        id="attendee-email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                    />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="submitting">
                        {{ submitting ? 'Saving...' : 'Join the list' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
