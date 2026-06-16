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
        // Write draft to the target repo (for reference)
        file_put_contents($repoPath.'/draft.yaml', $this->generateDraft($manifest));

        // Run blueprint from foundry's own artisan (blueprint is installed here)
        // and capture which files it generates
        $draftPath = base_path('draft-'.uniqid().'.yaml');
        file_put_contents($draftPath, $this->generateDraft($manifest));

        $result = Process::run('php '.base_path('artisan')." blueprint:build {$draftPath}");

        @unlink($draftPath);

        // Parse generated file paths from blueprint output and copy to target repo
        preg_match_all('/\[([^\]]+)\] built successfully/', $result->output(), $matches);

        foreach ($matches[1] as $generatedPath) {
            $srcPath = base_path($generatedPath);
            $destPath = $repoPath.'/'.$generatedPath;

            if (file_exists($srcPath)) {
                @mkdir(dirname($destPath), 0755, true);
                copy($srcPath, $destPath);
                @unlink($srcPath);
            }
        }

        Process::path($repoPath)->run('git add -A');
        Process::path($repoPath)->run(
            'git -c user.email="foundry@conduit-ui.com" -c user.name="Foundry" '.
            'commit -m "feat: scaffold '.addslashes($manifest->name).'"'
        );
    }
}
