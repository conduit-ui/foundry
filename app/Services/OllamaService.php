<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function generate(string $prompt, string $model = 'qwen2.5-coder:7b'): string
    {
        $response = Http::post(config('services.ollama.url').'/api/generate', [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ]);

        return $response->json('response', '');
    }
}
