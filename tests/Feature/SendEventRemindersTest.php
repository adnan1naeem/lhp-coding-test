<?php

use App\Jobs\SendEventReminder;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues three day reminders for events in the window', function () {
    Queue::fake();
    Carbon::setTestNow('2026-06-15 10:00:00');

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addHours(72)->addMinutes(30)->timestamp,
        'payload' => ['name' => 'Soon Show'],
    ]);

    $attendee = EventAttendee::create([
        'event_id' => $event->id,
        'name' => 'Pat',
        'email' => 'pat@example.com',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendEventReminder::class, function (SendEventReminder $job) use ($attendee) {
        return $job->attendee->is($attendee) && $job->reminderType === '3d';
    });

    expect($attendee->fresh()->reminder_3d_sent_at)->not->toBeNull();
});

it('queues twenty four hour reminders for events in the window', function () {
    Queue::fake();
    Carbon::setTestNow('2026-06-15 10:00:00');

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addHours(24)->addMinutes(15)->timestamp,
        'payload' => ['name' => 'Tomorrow Show'],
    ]);

    $attendee = EventAttendee::create([
        'event_id' => $event->id,
        'name' => 'Pat',
        'email' => 'pat@example.com',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendEventReminder::class, function (SendEventReminder $job) use ($attendee) {
        return $job->attendee->is($attendee) && $job->reminderType === '24h';
    });

    expect($attendee->fresh()->reminder_24h_sent_at)->not->toBeNull();
});

it('does not resend reminders that were already sent', function () {
    Queue::fake();
    Carbon::setTestNow('2026-06-15 10:00:00');

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addHours(24)->addMinutes(15)->timestamp,
    ]);

    EventAttendee::create([
        'event_id' => $event->id,
        'name' => 'Pat',
        'email' => 'pat@example.com',
        'reminder_24h_sent_at' => now(),
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});
