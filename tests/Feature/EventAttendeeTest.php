<?php

use App\Jobs\SendAttendeeConfirmation;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('registers an attendee and queues a confirmation email', function () {
    Queue::fake();

    $event = Event::factory()->create(['status' => 'published']);

    $response = $this->postJson(route('events.attendees.store', $event), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $response->assertCreated()
        ->assertJsonPath('attendee.email', 'jane@example.com');

    expect(EventAttendee::count())->toBe(1);
    Queue::assertPushed(SendAttendeeConfirmation::class);
});

it('rejects duplicate attendee emails for the same event', function () {
    $event = Event::factory()->create(['status' => 'published']);

    EventAttendee::create([
        'event_id' => $event->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $this->postJson(route('events.attendees.store', $event), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates attendee input', function () {
    $event = Event::factory()->create();

    $this->postJson(route('events.attendees.store', $event), [
        'name' => '',
        'email' => 'not-an-email',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});
