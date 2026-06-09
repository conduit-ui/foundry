<?php

use App\Data\ManifestData;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Http;

function makeManifest(string $name = 'my-service'): ManifestData
{
    return ManifestData::from([
        'name' => $name,
        'description' => 'A test service',
        'stack' => [
            'framework' => 'laravel',
            'type' => 'microservice',
            'php' => '8.2',
            'auth' => 'none',
            'queue' => 'redis',
            'db' => 'sqlite',
        ],
        'models' => [],
        'bifrost' => ['source' => $name, 'events' => []],
        'quality' => ['runner' => true, 'coverage' => 100, 'pint' => true, 'phpstan' => true],
        'deploy' => ['host' => 'odin', 'quadlet' => true],
    ]);
}

describe('GitHubService', function () {
    beforeEach(function () {
        config(['services.github.token' => 'test-token', 'services.github.org' => 'test-org']);
    });

    describe('createRepo', function () {
        it('posts to the GitHub orgs repos endpoint and returns json', function () {
            Http::fake([
                'https://api.github.com/orgs/test-org/repos' => Http::response(['id' => 1, 'name' => 'my-service'], 201),
            ]);

            $service = new GitHubService;
            $result = $service->createRepo(makeManifest());

            expect($result)->toBe(['id' => 1, 'name' => 'my-service']);

            Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/orgs/test-org/repos'
                && $request->method() === 'POST'
                && $request['name'] === 'my-service'
                && $request['description'] === 'A test service'
                && $request['private'] === true
                && $request['auto_init'] === true
            );
        });

        it('sends bearer token and accept headers', function () {
            Http::fake([
                'https://api.github.com/orgs/test-org/repos' => Http::response(['id' => 1], 201),
            ]);

            $service = new GitHubService;
            $service->createRepo(makeManifest());

            Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('Accept', 'application/vnd.github+json')
            );
        });

        it('throws a RuntimeException when the request fails', function () {
            Http::fake([
                'https://api.github.com/orgs/test-org/repos' => Http::response(['message' => 'Unprocessable Entity'], 422),
            ]);

            $service = new GitHubService;

            expect(fn () => $service->createRepo(makeManifest()))
                ->toThrow(RuntimeException::class, 'Failed to create GitHub repository: HTTP 422');
        });
    });

    describe('openIssue', function () {
        it('posts to the repos issues endpoint and returns json', function () {
            Http::fake([
                'https://api.github.com/repos/test-org/my-service/issues' => Http::response(['number' => 7], 201),
            ]);

            $service = new GitHubService;
            $result = $service->openIssue('my-service', 'Setup: bootstrap', 'Do the thing');

            expect($result)->toBe(['number' => 7]);

            Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/test-org/my-service/issues'
                && $request['title'] === 'Setup: bootstrap'
                && $request['body'] === 'Do the thing'
            );
        });

        it('throws a RuntimeException when the request fails', function () {
            Http::fake([
                'https://api.github.com/repos/test-org/my-service/issues' => Http::response([], 500),
            ]);

            $service = new GitHubService;

            expect(fn () => $service->openIssue('my-service', 'Title', 'Body'))
                ->toThrow(RuntimeException::class, 'Failed to open issue: Title: HTTP 500');
        });
    });
});
