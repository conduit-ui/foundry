<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class SkeletonService
{
    public function clone(string $targetPath): void
    {
        Process::run("git clone git@github.com:the-shit/agent-skeleton.git {$targetPath}");
    }

    public function push(string $repoPath, string $repoUrl): void
    {
        Process::path($repoPath)->run("git remote set-url origin {$repoUrl}");
        Process::path($repoPath)->run('git push -u origin main');
    }
}
