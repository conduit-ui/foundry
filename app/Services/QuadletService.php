<?php

namespace App\Services;

use App\Data\ManifestData;
use Illuminate\Support\Facades\Process;

class QuadletService
{
    public function generate(ManifestData $manifest): string
    {
        $name = $manifest->name;
        $host = config('services.deploy.host');

        return <<<QUADLET
        [Unit]
        Description={$name} — scaffolded by Foundry
        After=network.target

        [Container]
        Image=localhost/{$name}:latest
        Network=host
        EnvironmentFile=/opt/{$name}/.env

        [Service]
        Restart=always

        [Install]
        WantedBy=default.target
        QUADLET;
    }

    public function deploy(ManifestData $manifest, string $repoPath): void
    {
        $name = $manifest->name;
        $host = config('services.deploy.host');
        $user = config('services.deploy.user');
        $quadlet = $this->generate($manifest);

        $escapedQuadlet = escapeshellarg($quadlet);
        $quadletPath = "/home/{$user}/.config/containers/systemd/{$name}.container";

        Process::run("ssh {$user}@{$host} \"echo {$escapedQuadlet} > {$quadletPath} && systemctl --user daemon-reload && systemctl --user enable --now {$name}\"");
    }
}
