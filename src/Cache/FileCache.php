<?php

declare(strict_types=1);

namespace App\Cache;

final class FileCache implements Cache
{
    public function __construct(
        private string $path
    ) {
    }

    public function get(string $key): ?string
    {
        if (!$this->has($key)) {
            return null;
        }

        $filePath = $this->getFilePath($key);

        return file_get_contents($filePath);
    }

    public function set(string $key, string $value): bool
    {
        $filePath = $this->getFilePath($key);

        return (bool)file_put_contents($filePath, $value);
    }

    public function has(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        return file_exists($filePath);
    }

    private function getFilePath(string $key): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR . $key;
    }
}
