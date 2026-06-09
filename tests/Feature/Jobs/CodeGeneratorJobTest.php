<?php

use App\Enums\ScaffoldStatus;
use App\Jobs\CodeGeneratorJob;
use App\Models\ScaffoldRun;
use Illuminate\Contracts\Queue\ShouldQueue;

describe('CodeGeneratorJob', function () {
    it('implements ShouldQueue', function () {
        expect(CodeGeneratorJob::class)->toImplement(ShouldQueue::class);
    });

    it('holds the scaffold run as a public property', function () {
        $run = ScaffoldRun::create([
            'manifest' => [],
            'status' => ScaffoldStatus::Queued,
        ]);

        $job = new CodeGeneratorJob($run);

        expect($job->run)->toBe($run);
    });

    it('handle completes without error', function () {
        $run = ScaffoldRun::create([
            'manifest' => [],
            'status' => ScaffoldStatus::Queued,
        ]);

        $job = new CodeGeneratorJob($run);

        expect(fn () => $job->handle())->not->toThrow(Throwable::class);
    });
});
