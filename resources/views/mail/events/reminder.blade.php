<x-mail::message>
# {{ $reminderType === '3d' ? '3 days to go' : 'Happening tomorrow' }}

Hi {{ $attendee->name }},

This is a friendly reminder about **{{ $event->payload['name'] ?? 'your event' }}**.

@if ($reminderType === '3d')
The event is coming up in about three days.
@else
The event is happening in about 24 hours.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
