<?php

namespace App\Jobs;

use App\Mail\EventReminder;
use App\Models\EventAttendee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEventReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EventAttendee $attendee,
        public string $reminderType,
    ) {}

    public function handle(): void
    {
        $this->attendee->load('event');

        Mail::to($this->attendee->email)->send(
            new EventReminder($this->attendee, $this->attendee->event, $this->reminderType),
        );
    }
}
