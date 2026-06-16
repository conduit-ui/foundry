<?php

namespace App\Services;

use App\Data\ManifestData;
use Illuminate\Support\Facades\Process;

class BlueprintService
{
    public function generateDraft(ManifestData $manifest): string
    {
        $yaml = "models:\n";

        foreach ($manifest->models as $model) {
            $yaml .= "  {$model->name}:\n";

            foreach ($model->attributes as $attribute) {
                [$name, $type] = str_contains($attribute, ':')
                    ? explode(':', $attribute, 2)
                    : [$attribute, 'string'];

                $yaml .= "    {$name}: {$type}\n";
            }

            if ($model->relationships->count() > 0) {
                $yaml .= "    relationships:\n";

                foreach ($model->relationships as $relationship) {
                    $yaml .= "      {$relationship->type}: {$relationship->model}\n";
                }
            }
        }

        return $yaml;
    }

    public function build(ManifestData $manifest, string $repoPath): void
    {
        $draftPath = $repoPath.'/draft.yaml';

        file_put_contents($draftPath, $this->generateDraft($manifest));

        Process::path($repoPath)->run('php artisan blueprint:build draft.yaml');
    }
}
