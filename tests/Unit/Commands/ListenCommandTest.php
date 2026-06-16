<?php

use App\Commands\ListenCommand;
use App\Data\BifrostData;
use App\Data\DeployData;
use App\Data\Enums\AuthDriver;
use App\Data\Enums\DatabaseDriver;
use App\Data\ManifestData;
use App\Data\ModelData;
use App\Data\QualityData;
use App\Data\StackData;
use App\Models\ScaffoldRun;
use App\Services\ScaffoldPipeline;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Spatie\LaravelData\DataCollection;

function listenManifest(): ManifestData
{
    return new ManifestData(
        name: 'my-service',
        description: 'A service',
        stack: new StackData('laravel', 'microservice', '8.2', AuthDriver::None, 'redis', DatabaseDriver::Sqlite),
        models: new DataCollection(ModelData::class, []),
        bifrost: new BifrostData('my-service', []),
        quality: new QualityData(true, 100.0, true, true),
        deploy: new DeployData('odin', true),
    );
}

describe('ListenCommand', function () {
    it('subscribes to bifrost.foundry and dispatches pipeline for scaffold events', function () {
        Queue::fake();

        $manifest = listenManifest();
        $message = json_encode([
            'event' => 'foundry.scaffold.requested',
            'payload' => $manifest->toArray(),
        ]);

        $capturedCallback = null;

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.foundry'], Mockery::capture($capturedCallback));

        $pipeline = Mockery::mock(ScaffoldPipeline::class);
        $pipeline->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ManifestData::class))
            ->andReturn(new ScaffoldRun);

        app()->instance(ScaffoldPipeline::class, $pipeline);

        $command = new ListenCommand;
        $command->setLaravel(app());

        $output = new \Symfony\Component\Console\Output\BufferedOutput;
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArgvInput,
            $output,
        ));

        $command->handle($pipeline);

        $capturedCallback($message);
    });

    it('skips unknown events without dispatching', function () {
        $message = json_encode(['event' => 'some.other.event', 'payload' => []]);

        $capturedCallback = null;

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.foundry'], Mockery::capture($capturedCallback));

        $pipeline = Mockery::mock(ScaffoldPipeline::class);
        $pipeline->shouldNotReceive('dispatch');

        app()->instance(ScaffoldPipeline::class, $pipeline);

        $command = new ListenCommand;
        $command->setLaravel(app());

        $output = new \Symfony\Component\Console\Output\BufferedOutput;
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArgvInput,
            $output,
        ));

        $command->handle($pipeline);

        $capturedCallback($message);
    });

    it('handles missing event key without dispatching', function () {
        $message = json_encode(['payload' => []]);

        $capturedCallback = null;

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.foundry'], Mockery::capture($capturedCallback));

        $pipeline = Mockery::mock(ScaffoldPipeline::class);
        $pipeline->shouldNotReceive('dispatch');

        app()->instance(ScaffoldPipeline::class, $pipeline);

        $command = new ListenCommand;
        $command->setLaravel(app());

        $output = new \Symfony\Component\Console\Output\BufferedOutput;
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArgvInput,
            $output,
        ));

        $command->handle($pipeline);

        $capturedCallback($message);
    });
});
