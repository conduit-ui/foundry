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

];
