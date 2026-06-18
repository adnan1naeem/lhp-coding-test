# Implementation Decisions

## Layouts

- **Visual 1** uses a responsive **card grid** with a hero image carousel, top filter bar, and staggered fade-in animations.
- **Visual 2** uses a **chronological timeline** grouped by month, with a sticky sidebar for filters and alternating left/right cards plus a horizontal image strip.

The two pages share filters, attendee registration, and data loading, but deliberately use different card composition and motion so they do not feel like the same page twice.

## Geocoding

Events only store latitude and longitude. Human-readable locations are derived offline by matching each coordinate to the nearest city anchor from the seeded anchor list (`config/cities.php`). This avoids external geocoding APIs, keeps lookups fast at scale, and produces stable city labels for filtering.

Run `php artisan events:backfill-locations` once on an existing large database so `city_slug` is populated for location filtering.

## Date & time

- The canonical event start timestamp is `events.created_time`.
- The API also exposes `payload.schedule.ends_at` as `ends_at`.
- The UI shows **two times** for each event:
  - **Your time** — browser local timezone via `Intl.DateTimeFormat`
  - **Event time** — IANA timezone from the nearest city anchor
- Date filters apply to `created_time` using UTC day boundaries.

## Scale

The seeded dataset can contain over a million events. Visual browsing therefore:

- defaults to `status=published`
- paginates through `/events/visual-data`
- filters on indexed `city_slug` and `created_time`
- uses chunked artisan commands for one-time backfills

## Images

Six local JPEG placeholders live in `public/images/events/`. Each event receives three images, assigned deterministically from the event UUID when rows are not stored yet. Run `php artisan events:attach-images` to persist image rows in bulk for an existing database.

## Attendees & email

- Attendee registration is public (name + email) with a unique constraint per event.
- Confirmation email is queued immediately via `SendAttendeeConfirmation`.
- Reminder emails are sent by an hourly scheduled command using one-hour windows:
  - 3 days before (`created_time` in `[now+72h, now+73h)`)
  - 24 hours before (`created_time` in `[now+24h, now+25h)`)
- `reminder_3d_sent_at` and `reminder_24h_sent_at` prevent duplicate reminders.
- Local development uses the `log` mail driver; messages appear in `storage/logs/laravel.log` and Pail.

## Email delivery

Laravel Mailables + database queue are used instead of adding a separate mail SDK. This matches the existing starter kit setup and works with `composer dev`, which already runs `queue:listen`.
