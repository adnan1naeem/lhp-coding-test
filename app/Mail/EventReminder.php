<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventAttendee $attendee,
        public Event $event,
        public string $reminderType,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->event->payload['name'] ?? 'your event';
        $prefix = $this->reminderType === '3d' ? '3 days to go' : 'Tomorrow';

        return new Envelope(
            subject: "{$prefix}: {$title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.events.reminder',
        );
    }
}
