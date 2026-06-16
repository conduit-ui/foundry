<?php

namespace App\Jobs;

use App\Data\ManifestData;
use App\Enums\ScaffoldStatus;
use App\Models\ScaffoldRun;
use App\Services\BlueprintService;
use App\Services\OllamaService;
use App\Services\QuadletService;
use App\Services\SkeletonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CodeGeneratorJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly ScaffoldRun $run,
        public readonly ManifestData $manifest,
    ) {}

    public function handle(
        SkeletonService $skeleton,
        BlueprintService $blueprint,
        OllamaService $ollama,
        QuadletService $quadlet,
    ): void {
        $run = $this->run->fresh();
        $run->update(['status' => ScaffoldStatus::Running]);

        $tmpPath = sys_get_temp_dir().'/'.$this->manifest->name.'-'.uniqid();

        try {
            $skeleton->clone($tmpPath);
            $blueprint->build($this->manifest, $tmpPath);

            $customization = $ollama->generate(
                "Given this Laravel app manifest: {$this->manifest->name} — {$this->manifest->description}. ".
                'Suggest any additional scopes, accessors, or business logic methods worth adding to the generated models. Be concise.'
            );

            $skeleton->push(
                $tmpPath,
                'git@github.com:'.config('services.github.org').'/'.$this->manifest->name.'.git'
            );

            $quadlet->deploy($this->manifest, $tmpPath);

            $run->update([
                'status' => ScaffoldStatus::Complete,
                'output' => $customization,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => ScaffoldStatus::Failed,
                'output' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
