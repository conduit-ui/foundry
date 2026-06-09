<?php

namespace App\Services;

use App\Data\ManifestData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BifrostService
{
    private string $url;

    private string $token;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.bifrost.url'), '/');
        $this->token = (string) config('services.bifrost.token');
    }

    public function registerSource(ManifestData $manifest): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post("{$this->url}/api/sources", [
            'name' => $manifest->bifrost->source,
            'signing_secret' => Str::random(40),
            'is_active' => true,
        ]);

        $this->ensureSuccess($response, 'Failed to register Bifrost source');

        return $response->json();
    }

    private function ensureSuccess(Response $response, string $message): void
    {
        if ($response->failed()) {
            throw new \RuntimeException("{$message}: HTTP {$response->status()}");
        }
    }
}
