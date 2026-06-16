<?php

namespace App\Commands;

use App\Data\ManifestData;
use App\Services\ScaffoldPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ListenCommand extends Command
{
    protected $signature = 'foundry:listen';

    protected $description = 'Subscribe to bifrost.foundry Redis channel and route scaffold events';

    public function handle(ScaffoldPipeline $pipeline): void
    {
        $this->info('Listening on bifrost.foundry...');

        Redis::subscribe(['bifrost.foundry'], function (string $message) use ($pipeline) {
            $data = json_decode($message, true);
            $event = $data['event'] ?? null;

            if ($event !== 'foundry.scaffold.requested') {
                $this->line("Skipping unknown event: {$event}");

                return;
            }

            $manifest = ManifestData::from($data['payload']);
            $run = $pipeline->dispatch($manifest);

            $this->info("Scaffold queued: {$run->id}");
        });
    }
}
