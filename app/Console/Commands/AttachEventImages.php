<?php

namespace App\Console\Commands;

use App\Support\EventImageAssigner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AttachEventImages extends Command
{
    protected const CHUNK = 4000;

    protected $signature = 'events:attach-images
                            {--chunk=4000 : Events per batch}
                            {--only-missing : Only attach to events without images}';

    protected $description = 'Attach 3 placeholder images per event';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $onlyMissing = (bool) $this->option('only-missing');
        $now = now();

        $query = DB::table('events')->select('id');

        if ($onlyMissing) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('event_images')
                    ->whereColumn('event_images.event_id', 'events.id');
            });
        }

        $total = (clone $query)->count();
        $this->info("Attaching images for {$total} events...");

        $done = 0;
        $start = microtime(true);

        $query->orderBy('id')->chunkById($chunk, function ($events) use (&$done, $total, $now) {
            $rows = [];

            foreach ($events as $event) {
                foreach (EventImageAssigner::pathsForEvent($event->id) as $image) {
                    $rows[] = [
                        'event_id' => $event->id,
                        'path' => $image['path'],
                        'sort_order' => $image['sort_order'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, self::CHUNK * 3) as $batch) {
                DB::table('event_images')->insert($batch);
            }

            $done += $events->count();
            $this->line("  attached {$done}/{$total}");
        }, 'id');

        $elapsed = round(microtime(true) - $start, 1);
        $this->info("Done in {$elapsed}s.");

        return self::SUCCESS;
    }
}
