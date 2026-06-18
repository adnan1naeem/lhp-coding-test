<?php

namespace App\Console\Commands;

use App\Jobs\SendEventReminder;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send 3-day and 24-hour reminder emails to event attendees';

    public function handle(): int
    {
        $this->sendForWindow('3d', 72, 'reminder_3d_sent_at');
        $this->sendForWindow('24h', 24, 'reminder_24h_sent_at');

        return self::SUCCESS;
    }

    private function sendForWindow(string $type, int $hoursAhead, string $sentColumn): void
    {
        $windowStart = now()->addHours($hoursAhead)->timestamp;
        $windowEnd = now()->addHours($hoursAhead + 1)->timestamp;

        $events = Event::query()
            ->where('status', 'published')
            ->whereBetween('created_time', [$windowStart, $windowEnd])
            ->get(['id', 'payload', 'created_time']);

        if ($events->isEmpty()) {
            $this->line("No events in the {$type} reminder window.");

            return;
        }

        $sent = 0;

        foreach ($events as $event) {
            $attendees = EventAttendee::query()
                ->where('event_id', $event->id)
                ->whereNull($sentColumn)
                ->get();

            foreach ($attendees as $attendee) {
                SendEventReminder::dispatch($attendee, $type);
                $attendee->forceFill([$sentColumn => now()])->save();
                $sent++;
            }
        }

        $this->info("Queued {$sent} {$type} reminder(s).");
    }
}
