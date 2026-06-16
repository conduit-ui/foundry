<?php

use App\Data\BifrostData;
use App\Data\DeployData;
use App\Data\Enums\AuthDriver;
use App\Data\Enums\DatabaseDriver;
use App\Data\ManifestData;
use App\Data\QualityData;
use App\Data\StackData;
use App\Enums\ScaffoldStatus;
use App\Jobs\CodeGeneratorJob;
use App\Models\ScaffoldRun;
use App\Services\BlueprintService;
use App\Services\OllamaService;
use App\Services\QuadletService;
use App\Services\SkeletonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\LaravelData\DataCollection;

function makeCodeGenManifest(): ManifestData
{
    return new ManifestData(
        name: 'billing-service',
        description: 'Handles billing',
        stack: new StackData('laravel', 'microservice', '8.2', AuthDriver::None, 'redis', DatabaseDriver::Sqlite),
        models: new DataCollection(\App\Data\ModelData::class, []),
        bifrost: new BifrostData('billing-service', []),
        quality: new QualityData(true, 100.0, true, true),
        deploy: new DeployData('odin', true),
    );
}

function makeRun(): ScaffoldRun
{
    return ScaffoldRun::create([
        'manifest' => [],
        'status' => ScaffoldStatus::Queued,
    ]);
}

describe('CodeGeneratorJob', function () {
    it('implements ShouldQueue', function () {
        expect(CodeGeneratorJob::class)->toImplement(ShouldQueue::class);
    });

    it('holds run and manifest as public properties', function () {
        $run = makeRun();
        $manifest = makeCodeGenManifest();
        $job = new CodeGeneratorJob($run, $manifest);

        expect($job->run->id)->toBe($run->id)
            ->and($job->manifest->name)->toBe('billing-service');
    });

    it('completes successfully and updates run status', function () {
        $run = makeRun();
        $manifest = makeCodeGenManifest();

        $skeleton = Mockery::mock(SkeletonService::class);
        $skeleton->shouldReceive('clone')->once();
        $skeleton->shouldReceive('push')->once();

        $blueprint = Mockery::mock(BlueprintService::class);
        $blueprint->shouldReceive('build')->once();

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('generate')->once()->andReturn('Add a scope for active subscriptions.');

        $quadlet = Mockery::mock(QuadletService::class);
        $quadlet->shouldReceive('deploy')->once();

        $job = new CodeGeneratorJob($run, $manifest);
        $job->handle($skeleton, $blueprint, $ollama, $quadlet);

        $run->refresh();
        expect($run->status)->toBe(ScaffoldStatus::Complete)
            ->and($run->completed_at)->not->toBeNull();
    });

    it('completes successfully when ollama throws, storing empty output', function () {
        $run = makeRun();
        $manifest = makeCodeGenManifest();

        $skeleton = Mockery::mock(SkeletonService::class);
        $skeleton->shouldReceive('clone')->once();
        $skeleton->shouldReceive('push')->once();

        $blueprint = Mockery::mock(BlueprintService::class);
        $blueprint->shouldReceive('build')->once();

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('generate')->once()->andThrow(new \RuntimeException('Connection refused'));

        $quadlet = Mockery::mock(QuadletService::class);
        $quadlet->shouldReceive('deploy')->once();

        $job = new CodeGeneratorJob($run, $manifest);
        $job->handle($skeleton, $blueprint, $ollama, $quadlet);

        $run->refresh();
        expect($run->status)->toBe(ScaffoldStatus::Complete)
            ->and($run->output)->toBe('')
            ->and($run->completed_at)->not->toBeNull();
    });

    it('marks run as failed and re-throws when a service throws', function () {
        $run = makeRun();
        $manifest = makeCodeGenManifest();

        $skeleton = Mockery::mock(SkeletonService::class);
        $skeleton->shouldReceive('clone')->once()->andThrow(new \RuntimeException('Clone failed'));

        $blueprint = Mockery::mock(BlueprintService::class);
        $ollama = Mockery::mock(OllamaService::class);
        $quadlet = Mockery::mock(QuadletService::class);

        $job = new CodeGeneratorJob($run, $manifest);

        expect(fn () => $job->handle($skeleton, $blueprint, $ollama, $quadlet))
            ->toThrow(\RuntimeException::class, 'Clone failed');

        $run->refresh();
        expect($run->status)->toBe(ScaffoldStatus::Failed)
            ->and($run->output)->toBe('Clone failed');
    });
});
