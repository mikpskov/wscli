<?php

declare(strict_types=1);

namespace App\Tracker;

use App\Cache\Cache;
use App\Cache\FileCache;
use App\HttpClient\Exceptions\HttpClientException;
use App\HttpClient\CurlHttpClient;
use App\HttpClient\HttpClient;
use App\Utils\Date;
use Exception;
use SimpleXMLElement;

final class Worksnaps implements Tracker
{
    private const BASE_URL = 'https://api.worksnaps.com';

    private const REQUEST_CACHE_TTL = 600;

    private int $project;

    private int $user;

    private Date $date;

    public function __construct(
        array $config,
        ?string $date = null,
        private ?HttpClient $httpClient = null,
        private ?Cache $cache = null,
    ) {
        $this->project = $config['worksnaps']['project'];
        $this->user = $config['worksnaps']['user'];
        $this->setDate($date);

        $this->httpClient ??= new CurlHttpClient([
            'base_uri' => self::BASE_URL,
            'auth' => [$config['worksnaps']['token'], 'ignored'],
            'proxy' => $config['worksnaps']['proxy'] ?? null,
        ]);

        $this->cache ??= new FileCache($config['cache']['path']);
    }

    public function setDate(?string $date = null): void
    {
        $this->date = new Date($date);
    }

    public function getSumForMonth(): int
    {
        return $this->getReportSum(
            $this->date->startOfMonth()->getTimestamp(),
            $this->date->endOfDay()->getTimestamp(),
        );
    }

    public function getSumForDay(): int
    {
        return $this->getReportSum(
            $this->date->startOfDay()->getTimestamp(),
            $this->date->endOfDay()->getTimestamp(),
        );
    }

    private function getReportSum(int $from, int $to): ?int
    {
        $summary = $this->requestCached("/api/projects/$this->project/reports", [
            'name' => 'time_summary',
            'user_ids' => $this->user,
            'from_timestamp' => $from,
            'to_timestamp' => $to,
        ]);

        if ($summary->count() <= 0) {
            return 0;
        }

        $timeEntries = ((array)$summary)['time_entry'];
        if ($timeEntries instanceof SimpleXMLElement) {
            $timeEntries = [$timeEntries];
        }

        return array_reduce(
            $timeEntries,
            fn($carry, $entry) => $carry += (int)$entry->duration_in_minutes,
        );
    }

    private function request(string $uri, array $queryParams): SimpleXMLElement
    {
        try {
            $result = $this->httpClient->get($uri, ['query' => $queryParams]);
            $result = $result->getBody();
        } catch (HttpClientException $e) {
            exit("Worksnaps API Error: {$e->getMessage()}" . PHP_EOL);
        }

        try {
            return new SimpleXMLElement($result);
        } catch (Exception $e) {
            exit("Error XML parsing: {$e->getMessage()}" . PHP_EOL);
        }
    }

    private function requestCached(string $uri, array $queryParams): SimpleXMLElement
    {
        $paramsHash = md5($uri . json_encode($queryParams));
        $cacheKey = "response_{$paramsHash}.xlm";

        if ($result = $this->cache->get($cacheKey, self::REQUEST_CACHE_TTL)) {
            try {
                return new SimpleXMLElement($result);
            } catch (Exception) {
            }
        }

        $result = $this->request($uri, $queryParams);
        $this->cache->set($cacheKey, $result->asXML());

        return $result;
    }
}
