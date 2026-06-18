<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createVisualEvent(\App\Models\User $user, array $overrides = []): \App\Models\Event
{
    $event = \App\Models\Event::factory()->for($user)->create(array_merge([
        'status' => 'published',
        'created_time' => strtotime('2026-07-15 18:00:00 UTC'),
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'city_slug' => 'new-york',
        'city_label' => 'New York, NY, USA',
        'payload' => [
            'name' => 'Summer Jazz Night',
            'description' => 'An evening of live jazz.',
            'venue' => ['name' => 'Blue Note'],
            'schedule' => [
                'starts_at' => strtotime('2026-07-15 18:00:00 UTC'),
                'ends_at' => strtotime('2026-07-15 21:00:00 UTC'),
            ],
        ],
    ], $overrides));

    \App\Models\EventImage::create([
        'event_id' => $event->id,
        'path' => 'images/events/concert-1.jpg',
        'sort_order' => 0,
    ]);

    \App\Models\EventImage::create([
        'event_id' => $event->id,
        'path' => 'images/events/concert-2.jpg',
        'sort_order' => 1,
    ]);

    return $event;
}
