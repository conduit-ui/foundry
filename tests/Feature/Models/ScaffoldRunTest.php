<?php

use App\Enums\ScaffoldStatus;
use App\Models\ScaffoldRun;

describe('ScaffoldRun', function () {
    it('creates a run with uuid primary key', function () {
        $run = ScaffoldRun::create([
            'manifest' => ['name' => 'my-service'],
            'status' => ScaffoldStatus::Queued,
        ]);

        expect($run->id)->toBeString()
            ->and(strlen($run->id))->toBe(36);
    });

    it('casts manifest to array', function () {
        $run = ScaffoldRun::create([
            'manifest' => ['name' => 'my-service', 'version' => 1],
            'status' => ScaffoldStatus::Queued,
        ]);

        $fresh = ScaffoldRun::find($run->id);

        expect($fresh->manifest)->toBeArray()
            ->and($fresh->manifest['name'])->toBe('my-service');
    });

    it('casts status to ScaffoldStatus enum', function () {
        $run = ScaffoldRun::create([
            'manifest' => [],
            'status' => ScaffoldStatus::Running,
        ]);

        $fresh = ScaffoldRun::find($run->id);

        expect($fresh->status)->toBe(ScaffoldStatus::Running);
    });

    it('stores nullable output', function () {
        $run = ScaffoldRun::create([
            'manifest' => [],
            'status' => ScaffoldStatus::Complete,
            'output' => 'Generated successfully',
        ]);

        $fresh = ScaffoldRun::find($run->id);

        expect($fresh->output)->toBe('Generated successfully');
    });

    it('stores nullable completed_at as datetime', function () {
        $now = now();

        $run = ScaffoldRun::create([
            'manifest' => [],
            'status' => ScaffoldStatus::Complete,
            'completed_at' => $now,
        ]);

        $fresh = ScaffoldRun::find($run->id);

        expect($fresh->completed_at)->not->toBeNull()
            ->and($fresh->completed_at->timestamp)->toBe($now->timestamp);
    });

    it('has null output and completed_at by default', function () {
        $run = ScaffoldRun::create([
            'manifest' => [],
            'status' => ScaffoldStatus::Queued,
        ]);

        expect($run->output)->toBeNull()
            ->and($run->completed_at)->toBeNull();
    });
});
