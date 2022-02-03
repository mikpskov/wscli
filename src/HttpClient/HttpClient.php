<?php

declare(strict_types=1);

namespace App\HttpClient;

use App\HttpClient\Exceptions\HttpClientException;

interface HttpClient
{
    /**
     * @throws HttpClientException
     */
    public function get(string $uri = '', array $options = []): HttpClient;

    public function getBody(): string;
}
