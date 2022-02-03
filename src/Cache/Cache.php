<?php

declare(strict_types=1);

namespace App\Cache;

interface Cache
{
    public function get(string $key, ?int $ttl = null): ?string;

    public function set(string $key, string $value): bool;

    public function has(string $key, ?int $ttl = null): bool;
}
