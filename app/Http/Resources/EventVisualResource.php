<?php

namespace App\Http\Resources;

use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventVisualResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->payload ?? [];
        $citySlug = $this->city_slug;
        $cityLabel = $this->city_label;

        if ($citySlug === null || $cityLabel === null) {
            $resolved = LocationResolver::resolve($this->latitude, $this->longitude);
            $citySlug ??= $resolved['slug'];
            $cityLabel ??= $resolved['label'];
        }

        $timezone = LocationResolver::timezoneForSlug($citySlug);
        $venueName = $payload['venue']['name'] ?? null;

        $images = $this->relationLoaded('images') && $this->images->isNotEmpty()
            ? $this->images->map(fn ($image) => [
                'url' => '/'.ltrim($image->path, '/'),
                'sort_order' => $image->sort_order,
            ])->values()->all()
            : collect(\App\Support\EventImageAssigner::pathsForEvent($this->id))
                ->map(fn (array $image) => [
                    'url' => '/'.ltrim($image['path'], '/'),
                    'sort_order' => $image['sort_order'],
                ])
                ->values()
                ->all();

        return [
            'id' => $this->id,
            'title' => $payload['name'] ?? 'Untitled event',
            'description' => $payload['description'] ?? '',
            'type' => $this->type,
            'status' => $this->status,
            'venue' => $venueName,
            'location' => [
                'label' => $cityLabel,
                'display' => $venueName ? "{$venueName} · {$cityLabel}" : $cityLabel,
                'lat' => $this->latitude,
                'lng' => $this->longitude,
                'city_slug' => $citySlug,
                'timezone' => $timezone,
            ],
            'schedule' => [
                'starts_at' => $this->starts_at,
                'ends_at' => $this->ends_at,
            ],
            'images' => $images,
            'attendee_count' => $this->attendees_count ?? 0,
        ];
    }
}
