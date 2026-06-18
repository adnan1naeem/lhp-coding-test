<?php

namespace App\Jobs;

use App\Mail\EventAttendeeConfirmation;
use App\Models\EventAttendee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAttendeeConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventAttendee $attendee) {}

    public function handle(): void
    {
        $this->attendee->load('event');

        Mail::to($this->attendee->email)->send(
            new EventAttendeeConfirmation($this->attendee, $this->attendee->event),
        );
    }
}
