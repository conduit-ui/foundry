<?php

namespace App\Jobs;

use App\Models\ScaffoldRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CodeGeneratorJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ScaffoldRun $run) {}

    public function handle(): void {}
}
