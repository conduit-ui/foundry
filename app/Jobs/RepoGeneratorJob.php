<?php

namespace App\Jobs;

use App\Data\ManifestData;
use App\Data\ModelData;
use App\Services\BifrostService;
use App\Services\GitHubService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RepoGeneratorJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ManifestData $manifest) {}

    public function handle(GitHubService $github, BifrostService $bifrost): void
    {
        $github->createRepo($this->manifest);

        $github->openIssue(
            $this->manifest->name,
            'Setup: initial project bootstrap',
            $this->setupIssueBody(),
        );

        foreach ($this->manifest->models as $model) {
            /** @var ModelData $model */
            $github->openIssue(
                $this->manifest->name,
                "Model: {$model->name}",
                $this->modelIssueBody($model),
            );
        }

        try {
            $bifrost->registerSource($this->manifest);
        } catch (\Throwable) {
            // Bifrost source registration is best-effort; source can be created via bifrost:source:add
        }
    }

    private function setupIssueBody(): string
    {
        $stack = $this->manifest->stack;

        return implode("\n", [
            '## Setup Checklist',
            '',
            "- [ ] Framework: {$stack->framework} {$stack->php}",
            "- [ ] Database: {$stack->db->value}",
            "- [ ] Auth: {$stack->auth->value}",
            "- [ ] Queue: {$stack->queue}",
            '- [ ] Install dependencies',
            '- [ ] Configure environment',
            '- [ ] Run migrations',
        ]);
    }

    private function modelIssueBody(ModelData $model): string
    {
        $attributes = implode("\n", array_map(
            fn (string $attr) => "- [ ] `{$attr}`",
            $model->attributes,
        ));

        $attributeSection = $attributes !== '' ? "\n## Attributes\n{$attributes}" : '';

        $relationships = $model->relationships->toCollection()
            ->map(fn ($rel) => "- [ ] {$rel->type}: {$rel->model}")
            ->implode("\n");

        $relationshipSection = $relationships !== '' ? "\n## Relationships\n{$relationships}" : '';

        return "## {$model->name}{$attributeSection}{$relationshipSection}";
    }
}
