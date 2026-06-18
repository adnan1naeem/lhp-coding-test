<x-mail::message>
# You're on the list

Hi {{ $attendee->name }},

Thanks for registering interest in **{{ $event->payload['name'] ?? 'this event' }}**.

We will send you reminder emails as the event approaches.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
