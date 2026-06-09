<?php

use App\Data\ManifestData;
use App\Services\BifrostService;
use Illuminate\Support\Facades\Http;

function makeBifrostManifest(string $source = 'my-service'): ManifestData
{
    return ManifestData::from([
        'name' => $source,
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
        'bifrost' => ['source' => $source, 'events' => []],
        'quality' => ['runner' => true, 'coverage' => 100, 'pint' => true, 'phpstan' => true],
        'deploy' => ['host' => 'odin', 'quadlet' => true],
    ]);
}

describe('BifrostService', function () {
    beforeEach(function () {
        config([
            'services.bifrost.url' => 'https://bifrost.example.com',
            'services.bifrost.token' => 'bifrost-token',
        ]);
    });

    describe('registerSource', function () {
        it('posts source registration to Bifrost and returns json', function () {
            Http::fake([
                'https://bifrost.example.com/api/sources' => Http::response(['id' => 42, 'name' => 'my-service'], 201),
            ]);

            $service = new BifrostService;
            $result = $service->registerSource(makeBifrostManifest());

            expect($result)->toBe(['id' => 42, 'name' => 'my-service']);

            Http::assertSent(fn ($request) => $request->url() === 'https://bifrost.example.com/api/sources'
                && $request->method() === 'POST'
                && $request['name'] === 'my-service'
                && $request['is_active'] === true
                && isset($request['signing_secret'])
            );
        });

        it('sends bearer token and accept headers', function () {
            Http::fake([
                'https://bifrost.example.com/api/sources' => Http::response(['id' => 1], 201),
            ]);

            $service = new BifrostService;
            $service->registerSource(makeBifrostManifest());

            Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer bifrost-token')
                && $request->hasHeader('Accept', 'application/json')
            );
        });

        it('strips trailing slash from configured url', function () {
            config(['services.bifrost.url' => 'https://bifrost.example.com/']);

            Http::fake([
                'https://bifrost.example.com/api/sources' => Http::response(['id' => 1], 201),
            ]);

            $service = new BifrostService;
            $service->registerSource(makeBifrostManifest());

            Http::assertSent(fn ($request) => $request->url() === 'https://bifrost.example.com/api/sources');
        });

        it('throws a RuntimeException when the request fails', function () {
            Http::fake([
                'https://bifrost.example.com/api/sources' => Http::response(['error' => 'conflict'], 409),
            ]);

            $service = new BifrostService;

            expect(fn () => $service->registerSource(makeBifrostManifest()))
                ->toThrow(RuntimeException::class, 'Failed to register Bifrost source: HTTP 409');
        });
    });
});
