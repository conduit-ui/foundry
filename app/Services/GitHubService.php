<?php

namespace App\Services;

use App\Data\ManifestData;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubService
{
    private string $token;

    private string $org;

    public function __construct()
    {
        $this->token = (string) config('services.github.token');
        $this->org = (string) config('services.github.org', 'conduit-ui');
    }

    public function createRepo(ManifestData $manifest): array
    {
        $response = $this->client()
            ->post("https://api.github.com/orgs/{$this->org}/repos", [
                'name' => $manifest->name,
                'description' => $manifest->description,
                'private' => true,
            ]);

        $this->ensureSuccess($response, 'Failed to create GitHub repository');

        return $response->json();
    }

    public function openIssue(string $repo, string $title, string $body): array
    {
        $response = $this->client()
            ->post("https://api.github.com/repos/{$this->org}/{$repo}/issues", [
                'title' => $title,
                'body' => $body,
            ]);

        $this->ensureSuccess($response, "Failed to open issue: {$title}");

        return $response->json();
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ]);
    }

    private function ensureSuccess(Response $response, string $message): void
    {
        if ($response->failed()) {
            throw new \RuntimeException("{$message}: HTTP {$response->status()}");
        }
    }
}
