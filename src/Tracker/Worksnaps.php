<?php

declare(strict_types=1);

namespace App\Tracker;

use App\Cache\Cache;
use App\Cache\FileCache;
use App\HttpClient\Exceptions\HttpClientException;
use App\HttpClient\CurlHttpClient;
use App\HttpClient\HttpClient;
use App\Utils\Date;
use App\ValueObjects\ReportEntry;
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

    public function getSumForMonth(): ReportEntry
    {
        return $this->getReportSum(
            $this->date->startOfMonth()->getTimestamp(),
            $this->date->endOfDay()->getTimestamp(),
        );
    }

    public function getSumForDay(): ReportEntry
    {
        return $this->getReportSum(
            $this->date->startOfDay()->getTimestamp(),
            $this->date->endOfDay()->getTimestamp(),
        );
    }

    private function getReportSum(int $from, int $to): ReportEntry
    {
        $summary = $this->requestCached("/api/projects/{$this->project}/users/{$this->user}/time_entries.xml", [
            'from_timestamp' => $from,
            'to_timestamp' => $to,
        ]);

        $timeEntries = $summary->count() > 0 ? ((array)$summary)['time_entry'] : [];
        if ($timeEntries instanceof SimpleXMLElement) {
            $timeEntries = [$timeEntries];
        }

        $duration = 0;
        $activityLevel = 0;
        $onlineEntriesCount = 0;
        foreach ($timeEntries as $timeEntry) {
            $duration += $timeEntry->duration_in_minutes;

            if ((string)$timeEntry->type === 'online') {
                $activityLevel += $timeEntry->activity_level;
                $onlineEntriesCount++;
            }
        }

        return new ReportEntry(
            duration: $duration,
            activity: ($onlineEntriesCount > 0 ? $activityLevel / $onlineEntriesCount * 10 : 0),
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

    private function requestCached(string $uri, array $queryParams = []): SimpleXMLElement
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
