<?php

namespace App\Data;

use App\Data\Enums\AuthDriver;
use App\Data\Enums\DatabaseDriver;
use Spatie\LaravelData\Data;

class StackData extends Data
{
    public function __construct(
        public readonly string $framework,
        public readonly string $type,
        public readonly string $php,
        public readonly AuthDriver $auth,
        public readonly string $queue,
        public readonly DatabaseDriver $db,
    ) {}
}
