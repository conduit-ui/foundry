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

    it('sets remote origin and force pushes to the given repo URL', function () {
        Process::fake();

        $service = new SkeletonService;
        $service->push('/tmp/my-app', 'git@github.com:conduit-ui/my-app.git');

        Process::assertRan('git remote set-url origin git@github.com:conduit-ui/my-app.git');
        Process::assertRan('git push -u origin main');
    });
});
