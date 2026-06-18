<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns enriched visual event data with pagination', function () {
    $user = User::factory()->create();
    createVisualEvent($user);

    $this->getJson(route('events.visual-data'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'title',
                    'description',
                    'location' => ['label', 'display', 'timezone', 'city_slug'],
                    'schedule' => ['starts_at', 'ends_at'],
                    'images' => [['url', 'sort_order']],
                    'attendee_count',
                ],
            ],
            'current_page',
            'last_page',
            'total',
            'stats',
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.title', 'Summer Jazz Night')
        ->assertJsonCount(2, 'data.0.images');
});

it('defaults to published events on the visual data endpoint', function () {
    $user = User::factory()->create();
    createVisualEvent($user, ['status' => 'published']);
    createVisualEvent($user, ['status' => 'draft', 'payload' => ['name' => 'Draft Show']]);

    $this->getJson(route('events.visual-data'))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.title', 'Summer Jazz Night');
});

it('filters visual events by city slug', function () {
    $user = User::factory()->create();
    createVisualEvent($user, ['city_slug' => 'new-york', 'city_label' => 'New York, NY, USA']);
    createVisualEvent($user, [
        'city_slug' => 'london',
        'city_label' => 'London, UK',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'payload' => ['name' => 'London Lights'],
    ]);

    $this->getJson(route('events.visual-data', ['city' => 'london']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.title', 'London Lights');
});

it('filters visual events by date range', function () {
    $user = User::factory()->create();
    createVisualEvent($user, ['created_time' => strtotime('2026-08-01 12:00:00 UTC')]);
    createVisualEvent($user, [
        'created_time' => strtotime('2026-09-01 12:00:00 UTC'),
        'payload' => ['name' => 'September Show'],
    ]);

    $this->getJson(route('events.visual-data', [
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-31',
    ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.title', 'Summer Jazz Night');
});

it('falls back to deterministic images when none are stored', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => ['name' => 'No Image Event'],
    ]);

    $this->getJson(route('events.visual-data'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $event->id)
        ->assertJsonCount(3, 'data.0.images');
});

it('renders visual pages with city options', function () {
    $this->get(route('events.visual1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualOne')
            ->has('cities', 75)
            ->where('filters.status', 'published')
        );

    $this->get(route('events.visual2'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualTwo')
            ->has('cities')
        );
});
