<?php

namespace App\Support;

class LocationResolver
{
    /** @var array<int, array{slug: string, label: string, lat: float, lng: float, timezone: string}>|null */
    private static ?array $anchors = null;

    /**
     * @return array{slug: string, label: string, timezone: string}
     */
    public static function resolve(?float $latitude, ?float $longitude): array
    {
        if ($latitude === null || $longitude === null) {
            return [
                'slug' => 'unknown',
                'label' => 'Unknown location',
                'timezone' => 'UTC',
            ];
        }

        $nearest = self::nearestAnchor($latitude, $longitude);

        return [
            'slug' => $nearest['slug'],
            'label' => $nearest['label'],
            'timezone' => $nearest['timezone'],
        ];
    }

    /**
     * @return array{slug: string, label: string, lat: float, lng: float, timezone: string}
     */
    public static function nearestAnchor(float $latitude, float $longitude): array
    {
        $anchors = self::anchors();
        $best = $anchors[0];
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($anchors as $anchor) {
            $distance = self::distanceSquared($latitude, $longitude, $anchor['lat'], $anchor['lng']);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        return $best;
    }

    /**
     * @return array<int, array{slug: string, label: string, lat: float, lng: float, timezone: string}>
     */
    public static function anchors(): array
    {
        if (self::$anchors === null) {
            self::$anchors = config('cities.anchors', []);
        }

        return self::$anchors;
    }

    /**
     * @return array<int, array{slug: string, label: string}>
     */
    public static function cityOptions(): array
    {
        return collect(self::anchors())
            ->map(fn (array $anchor) => [
                'slug' => $anchor['slug'],
                'label' => $anchor['label'],
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    public static function timezoneForSlug(?string $slug): string
    {
        if ($slug === null) {
            return 'UTC';
        }

        foreach (self::anchors() as $anchor) {
            if ($anchor['slug'] === $slug) {
                return $anchor['timezone'];
            }
        }

        return 'UTC';
    }

    private static function distanceSquared(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = $lat1 - $lat2;
        $dLng = $lng1 - $lng2;

        return ($dLat * $dLat) + ($dLng * $dLng);
    }
}
