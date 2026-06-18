<?php

namespace App\Support;

class EventImageAssigner
{
    /**
     * @return array<int, array{path: string, sort_order: int}>
     */
    public static function pathsForEvent(string $eventId): array
    {
        $placeholders = config('cities.image_placeholders', []);
        $count = count($placeholders);

        if ($count === 0) {
            return [];
        }

        $hash = crc32($eventId);
        $first = abs($hash) % $count;
        $second = ($first + 1) % $count;
        $third = ($first + 2) % $count;

        return [
            ['path' => $placeholders[$first], 'sort_order' => 0],
            ['path' => $placeholders[$second], 'sort_order' => 1],
            ['path' => $placeholders[$third], 'sort_order' => 2],
        ];
    }
}
