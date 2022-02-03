<?php

declare(strict_types=1);

namespace App\Cache;

final class FileCache implements Cache
{
    public function __construct(
        private string $path
    ) {
    }

    public function get(string $key, ?int $ttl = null): ?string
    {
        if (!$this->has($key, $ttl)) {
            return null;
        }

        $filePath = $this->getFilePath($key);

        return file_get_contents($filePath);
    }

    public function set(string $key, string $value): bool
    {
        $filePath = $this->getFilePath($key);

        return (bool)file_put_contents($filePath, $value, LOCK_EX);
    }

    public function has(string $key, ?int $ttl = null): bool
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {
            return $ttl === null || filemtime($filePath) > (time() - $ttl);
        }

        return false;
    }

    private function getFilePath(string $key): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR . $key;
    }
}
