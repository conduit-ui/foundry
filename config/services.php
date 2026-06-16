<?php

return [

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'org' => env('GITHUB_ORG', 'conduit-ui'),
    ],

    'bifrost' => [
        'url' => env('BIFROST_URL'),
        'token' => env('BIFROST_TOKEN'),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://loki:11434'),
    ],

    'deploy' => [
        'host' => env('DEPLOY_SSH_HOST', 'odin'),
        'user' => env('DEPLOY_SSH_USER', 'jordan'),
    ],

];
