<?php

namespace App\Services;

use App\Data\ManifestData;
use App\Enums\ScaffoldStatus;
use App\Jobs\CodeGeneratorJob;
use App\Jobs\RepoGeneratorJob;
use App\Models\ScaffoldRun;

class ScaffoldPipeline
{
    public function dispatch(ManifestData $manifest): ScaffoldRun
    {
        $run = ScaffoldRun::create([
            'manifest' => $manifest->toArray(),
            'status' => ScaffoldStatus::Queued,
        ]);

        RepoGeneratorJob::dispatch($manifest);
        CodeGeneratorJob::dispatch($run, $manifest);

        return $run;
    }
}
