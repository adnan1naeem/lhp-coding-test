<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventAttendeeConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventAttendee $attendee,
        public Event $event,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->event->payload['name'] ?? 'your event';

        return new Envelope(
            subject: "You're on the list: {$title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.events.attendee-confirmation',
        );
    }
}
