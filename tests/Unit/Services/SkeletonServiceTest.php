<?php

use App\Services\SkeletonService;
use Illuminate\Support\Facades\Process;

describe('SkeletonService', function () {
    it('clones the agent-skeleton repo to the target path', function () {
        Process::fake();

        $service = new SkeletonService;
        $service->clone('/tmp/my-app');

        Process::assertRan('git clone git@github.com:the-shit/agent-skeleton.git /tmp/my-app');
    });

    it('sets remote origin and pushes to the given repo URL', function () {
        Process::fake();

        $service = new SkeletonService;
        $service->push('/tmp/my-app', 'git@github.com:conduit-ui/my-app.git');

        Process::assertRan('git remote set-url origin git@github.com:conduit-ui/my-app.git');
        Process::assertRan('git push -u origin main');
    });

    it('throws when git push fails', function () {
        Process::fake([
            'git remote set-url*' => Process::result('', '', 0),
            'git push*' => Process::result('', 'error: failed to push', 1),
        ]);

        $service = new SkeletonService;

        expect(fn () => $service->push('/tmp/my-app', 'git@github.com:conduit-ui/my-app.git'))
            ->toThrow(\RuntimeException::class, 'Failed to push to');
    });
});
