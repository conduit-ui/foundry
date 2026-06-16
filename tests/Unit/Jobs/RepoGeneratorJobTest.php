<?php

use App\Data\ManifestData;
use App\Jobs\RepoGeneratorJob;
use App\Services\BifrostService;
use App\Services\GitHubService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mockery\MockInterface;

function makeJobManifest(array $models = []): ManifestData
{
    return ManifestData::from([
        'name' => 'billing-service',
        'description' => 'Handles billing',
        'stack' => [
            'framework' => 'laravel',
            'type' => 'microservice',
            'php' => '8.2',
            'auth' => 'github-socialite',
            'queue' => 'redis',
            'db' => 'sqlite',
        ],
        'models' => $models,
        'bifrost' => ['source' => 'billing-service', 'events' => ['subscription.created']],
        'quality' => ['runner' => true, 'coverage' => 100, 'pint' => true, 'phpstan' => true],
        'deploy' => ['host' => 'odin', 'quadlet' => true],
    ]);
}

describe('RepoGeneratorJob', function () {
    it('is queueable', function () {
        expect(RepoGeneratorJob::class)->toImplement(ShouldQueue::class);
    });

    it('stores the manifest as a public property', function () {
        $manifest = makeJobManifest();
        $job = new RepoGeneratorJob($manifest);

        expect($job->manifest)->toBe($manifest);
    });

    it('creates a repo, opens a setup issue, and registers the bifrost source when there are no models', function () {
        $manifest = makeJobManifest();

        $github = Mockery::mock(GitHubService::class, function (MockInterface $mock) use ($manifest) {
            $mock->shouldReceive('createRepo')->once()->with($manifest)->andReturn(['id' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Setup: initial project bootstrap', Mockery::type('string'))
                ->andReturn(['number' => 1]);
        });

        $bifrost = Mockery::mock(BifrostService::class, function (MockInterface $mock) use ($manifest) {
            $mock->shouldReceive('registerSource')->once()->with($manifest)->andReturn(['id' => 10]);
        });

        $job = new RepoGeneratorJob($manifest);
        $job->handle($github, $bifrost);
    });

    it('opens one issue per model in addition to the setup issue', function () {
        $manifest = makeJobManifest([
            ['name' => 'Plan', 'attributes' => ['name:string', 'price_cents:integer'], 'relationships' => [
                ['type' => 'hasMany', 'model' => 'Subscription'],
            ]],
            ['name' => 'Subscription', 'attributes' => [], 'relationships' => []],
        ]);

        $github = Mockery::mock(GitHubService::class, function (MockInterface $mock) use ($manifest) {
            $mock->shouldReceive('createRepo')->once()->with($manifest)->andReturn(['id' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Setup: initial project bootstrap', Mockery::type('string'))
                ->andReturn(['number' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Model: Plan', Mockery::type('string'))
                ->andReturn(['number' => 2]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Model: Subscription', Mockery::type('string'))
                ->andReturn(['number' => 3]);
        });

        $bifrost = Mockery::mock(BifrostService::class, function (MockInterface $mock) use ($manifest) {
            $mock->shouldReceive('registerSource')->once()->with($manifest)->andReturn(['id' => 10]);
        });

        $job = new RepoGeneratorJob($manifest);
        $job->handle($github, $bifrost);
    });

    it('includes stack details in the setup issue body', function () {
        $manifest = makeJobManifest();

        $setupBody = null;

        $github = Mockery::mock(GitHubService::class, function (MockInterface $mock) use (&$setupBody) {
            $mock->shouldReceive('createRepo')->once()->andReturn(['id' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->withArgs(function (string $repo, string $title, string $body) use (&$setupBody) {
                    $setupBody = $body;

                    return true;
                })
                ->andReturn(['number' => 1]);
        });

        $bifrost = Mockery::mock(BifrostService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerSource')->once()->andReturn(['id' => 10]);
        });

        $job = new RepoGeneratorJob($manifest);
        $job->handle($github, $bifrost);

        expect($setupBody)
            ->toContain('laravel')
            ->toContain('sqlite')
            ->toContain('github-socialite')
            ->toContain('redis');
    });

    it('includes attributes and relationships in model issue bodies', function () {
        $manifest = makeJobManifest([
            ['name' => 'Plan', 'attributes' => ['name:string', 'price_cents:integer'], 'relationships' => [
                ['type' => 'hasMany', 'model' => 'Subscription'],
            ]],
        ]);

        $modelBody = null;

        $github = Mockery::mock(GitHubService::class, function (MockInterface $mock) use (&$modelBody) {
            $mock->shouldReceive('createRepo')->once()->andReturn(['id' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Setup: initial project bootstrap', Mockery::type('string'))
                ->andReturn(['number' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Model: Plan', Mockery::type('string'))
                ->withArgs(function (string $repo, string $title, string $body) use (&$modelBody) {
                    $modelBody = $body;

                    return true;
                })
                ->andReturn(['number' => 2]);
        });

        $bifrost = Mockery::mock(BifrostService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerSource')->once()->andReturn(['id' => 10]);
        });

        $job = new RepoGeneratorJob($manifest);
        $job->handle($github, $bifrost);

        expect($modelBody)
            ->toContain('name:string')
            ->toContain('price_cents:integer')
            ->toContain('hasMany')
            ->toContain('Subscription');
    });

    it('completes without throwing when bifrost registration fails', function () {
        $manifest = makeJobManifest();

        $github = Mockery::mock(GitHubService::class, function (MockInterface $mock) use ($manifest) {
            $mock->shouldReceive('createRepo')->once()->with($manifest)->andReturn(['id' => 1]);
            $mock->shouldReceive('openIssue')->once()->andReturn(['number' => 1]);
        });

        $bifrost = Mockery::mock(BifrostService::class, function (MockInterface $mock) use ($manifest) {
            $mock->shouldReceive('registerSource')->once()->with($manifest)
                ->andThrow(new \RuntimeException('Failed to register Bifrost source: HTTP 404'));
        });

        $job = new RepoGeneratorJob($manifest);

        expect(fn () => $job->handle($github, $bifrost))->not->toThrow(\Throwable::class);
    });

    it('produces a model issue body without attribute or relationship sections when both are empty', function () {
        $manifest = makeJobManifest([
            ['name' => 'Tag', 'attributes' => [], 'relationships' => []],
        ]);

        $modelBody = null;

        $github = Mockery::mock(GitHubService::class, function (MockInterface $mock) use (&$modelBody) {
            $mock->shouldReceive('createRepo')->once()->andReturn(['id' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Setup: initial project bootstrap', Mockery::type('string'))
                ->andReturn(['number' => 1]);
            $mock->shouldReceive('openIssue')
                ->once()
                ->with('billing-service', 'Model: Tag', Mockery::type('string'))
                ->withArgs(function (string $repo, string $title, string $body) use (&$modelBody) {
                    $modelBody = $body;

                    return true;
                })
                ->andReturn(['number' => 2]);
        });

        $bifrost = Mockery::mock(BifrostService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerSource')->once()->andReturn(['id' => 10]);
        });

        $job = new RepoGeneratorJob($manifest);
        $job->handle($github, $bifrost);

        expect($modelBody)
            ->toBe('## Tag')
            ->not->toContain('Attributes')
            ->not->toContain('Relationships');
    });
});
