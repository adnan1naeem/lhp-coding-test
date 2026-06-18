<?php

namespace App\Console\Commands;

use App\Support\LocationResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillEventLocations extends Command
{
    protected $signature = 'events:backfill-locations
                            {--chunk=2000 : Rows per batch}
                            {--only-missing : Only update rows without city_slug}';

    protected $description = 'Backfill city_slug and city_label from nearest anchor city';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $onlyMissing = (bool) $this->option('only-missing');

        $query = DB::table('events')->select(['id', 'latitude', 'longitude', 'city_slug']);

        if ($onlyMissing) {
            $query->whereNull('city_slug');
        }

        $total = (clone $query)->count();
        $this->info("Backfilling locations for {$total} events...");

        $done = 0;
        $start = microtime(true);

        $query->orderBy('id')->chunkById($chunk, function ($events) use (&$done, $total) {
            DB::transaction(function () use ($events, &$done, $total) {
                foreach ($events as $event) {
                    $location = LocationResolver::resolve(
                        $event->latitude !== null ? (float) $event->latitude : null,
                        $event->longitude !== null ? (float) $event->longitude : null,
                    );

                    DB::table('events')->where('id', $event->id)->update([
                        'city_slug' => $location['slug'],
                        'city_label' => $location['label'],
                    ]);
                }
            });

            $done += $events->count();
            $this->line("  updated {$done}/{$total}");
        }, 'id');

        $elapsed = round(microtime(true) - $start, 1);
        $this->info("Done in {$elapsed}s.");

        return self::SUCCESS;
    }
}
