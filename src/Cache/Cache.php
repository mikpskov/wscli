<?php

declare(strict_types=1);

namespace App\Cache;

interface Cache
{
    public function get(string $key): ?string;

    public function set(string $key, string $value): bool;

    public function has(string $key): bool;
}
