<?php

declare(strict_types=1);

namespace App\HttpClient;

use App\HttpClient\Exceptions\HttpClientException;

final class HttpClient
{
    private const OPTIONS_DEFAULTS = [
        'curl' => [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 20,
        ],
    ];

    private array $defaultOptions;

    private string $body;

    public function __construct(array $options = [])
    {
        $this->defaultOptions = array_merge(self::OPTIONS_DEFAULTS, $options);
    }

    /**
     * @throws HttpClientException
     */
    public function get(string $uri = '', array $options = []): self
    {
        $options = $this->prepareDefaults($options);
        $url = $this->getUrl(
            ($options['base_uri'] ?? '') . $uri,
            $options['query'] ?? null
        );
        $auth = $options['auth'] ?? [];
        $proxy = $options['proxy'] ?? null;

        $curlOptions = ($options['curl'] ?? []) + [
                CURLOPT_URL => $url,
            ];

        // auth
        if (count($auth) >= 2) {
            $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $curlOptions[CURLOPT_USERPWD] = "$auth[0]:$auth[1]";
        }

        // proxy
        if (isset($proxy['host'], $proxy['port'])) {
            $curlOptions[CURLOPT_PROXY] = $proxy['host'];
            $curlOptions[CURLOPT_PROXYPORT] = $proxy['port'];

            if (isset($proxy['type'])) {
                $curlOptions[CURLOPT_PROXYTYPE] = $proxy['type'];
            }

            if (isset($proxy['user'], $proxy['pass'])) {
                $curlOptions[CURLOPT_PROXYUSERPWD] = "$proxy[user]:$proxy[pass]";
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if (!$response) {
            throw new HttpClientException($error ?: 'Empty body');
        }

        $this->body = $response;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    private function getUrl(string $uri, ?array $query = null): string
    {
        $url = $uri;

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function prepareDefaults(array $options): array
    {
        return array_merge($this->defaultOptions, $options);
    }
}
