<?php

use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;

describe('OllamaService', function () {
    it('posts to ollama generate endpoint and returns response text', function () {
        Http::fake([
            '*/api/generate' => Http::response(['response' => 'Add an active scope.'], 200),
        ]);

        $service = new OllamaService;
        $result = $service->generate('Suggest scopes for a Plan model.');

        expect($result)->toBe('Add an active scope.');
    });

    it('sends the correct model and prompt in the request body', function () {
        Http::fake([
            '*/api/generate' => Http::response(['response' => 'ok'], 200),
        ]);

        $service = new OllamaService;
        $service->generate('test prompt', 'qwen2.5-coder:7b');

        Http::assertSent(function ($request) {
            return $request->data()['model'] === 'qwen2.5-coder:7b'
                && $request->data()['prompt'] === 'test prompt'
                && $request->data()['stream'] === false;
        });
    });

    it('returns empty string when response key is missing', function () {
        Http::fake([
            '*/api/generate' => Http::response([], 200),
        ]);

        $service = new OllamaService;
        $result = $service->generate('prompt');

        expect($result)->toBe('');
    });
});
