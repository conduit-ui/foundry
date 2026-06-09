<?php

use App\Enums\ScaffoldStatus;
use App\Jobs\CodeGeneratorJob;
use App\Jobs\RepoGeneratorJob;
use App\Models\ScaffoldRun;
use Illuminate\Support\Facades\Queue;

describe('ScaffoldController', function () {
    $validManifest = [
        'name' => 'billing-service',
        'description' => 'Handles billing',
        'stack' => [
            'framework' => 'laravel',
            'type' => 'microservice',
            'php' => '8.2',
            'auth' => 'none',
            'queue' => 'redis',
            'db' => 'sqlite',
        ],
        'models' => [],
        'bifrost' => ['source' => 'billing-service', 'events' => []],
        'quality' => ['runner' => true, 'coverage' => 100, 'pint' => true, 'phpstan' => true],
        'deploy' => ['host' => 'odin', 'quadlet' => true],
    ];

    describe('POST /api/scaffold', function () use (&$validManifest) {
        it('creates a scaffold run and dispatches both jobs', function () use (&$validManifest) {
            Queue::fake();

            $response = $this->postJson('/api/scaffold', $validManifest);

            $response->assertStatus(201)
                ->assertJsonStructure(['id', 'status', 'status_url'])
                ->assertJsonPath('status', 'queued');

            Queue::assertPushed(RepoGeneratorJob::class);
            Queue::assertPushed(CodeGeneratorJob::class);

            $this->assertDatabaseHas('scaffold_runs', [
                'status' => 'queued',
            ]);
        });

        it('returns 422 for invalid manifest missing required fields', function () {
            Queue::fake();

            $response = $this->postJson('/api/scaffold', ['name' => 'only-name']);

            $response->assertStatus(422);

            Queue::assertNothingPushed();
        });

        it('returns 422 for completely empty body', function () {
            Queue::fake();

            $response = $this->postJson('/api/scaffold', []);

            $response->assertStatus(422);

            Queue::assertNothingPushed();
        });

        it('returns a status_url pointing to the show route', function () use (&$validManifest) {
            Queue::fake();

            $response = $this->postJson('/api/scaffold', $validManifest);

            $id = $response->json('id');
            $response->assertJsonPath('status_url', route('scaffold.show', $id));
        });
    });

    describe('GET /api/scaffold/{id}', function () {
        it('returns current status of an existing run', function () {
            $run = ScaffoldRun::create([
                'manifest' => ['name' => 'test'],
                'status' => ScaffoldStatus::Queued,
            ]);

            $response = $this->getJson("/api/scaffold/{$run->id}");

            $response->assertOk()
                ->assertJsonPath('id', $run->id)
                ->assertJsonPath('status', 'queued');
        });

        it('returns 404 for unknown id', function () {
            $response = $this->getJson('/api/scaffold/non-existent-id');

            $response->assertNotFound();
        });
    });
});
