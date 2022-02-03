<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Cache\Cache;
use App\Cache\FileCache;
use App\HttpClient\Exceptions\HttpClientException;
use App\HttpClient\CurlHttpClient;
use App\HttpClient\HttpClient;

final class IsDayOff extends Calendar
{
    private const BASE_URL = 'https://isdayoff.ru';
    private const WORKDAY_MINUTES = 480;
    private const PRE_HOLIDAY_MINUTES = 420;

    private const DAY_TYPES = [
        0 => self::WORKDAY_MINUTES,
        2 => self::PRE_HOLIDAY_MINUTES,
        4 => self::WORKDAY_MINUTES,
    ];

    private ?string $workDays;

    public function __construct(
        array $config,
        ?string $date = null,
        private ?HttpClient $httpClient = null,
        private ?Cache $cache = null,
    ) {
        parent::__construct($config, $date);

        $this->httpClient ??= new CurlHttpClient([
            'base_uri' => self::BASE_URL,
        ]);

        $this->cache ??= new FileCache($config['cache']['path']);
    }

    public function getForDay(): int
    {
        $start = $this->date->daysFromStartOfYear();

        return $this->get($start, $start);
    }

    public function getForMonth(bool $perMonth = false): int
    {
        $start = $this->date->startOfMonth()->daysFromStartOfYear();
        $end = $perMonth
            ? $this->date->endOfMonth()->daysFromStartOfYear()
            : $this->date->daysFromStartOfYear();

        return $this->get($start, $end);
    }

    private function get(int $start, int $end): int
    {
        $workDays = $this->getWorkDays();

        $minutes = 0;
        for ($dayNum = $start; $dayNum <= $end; $dayNum++) {
            if (!isset(self::DAY_TYPES[$workDays[$dayNum]])) {
                continue;
            }

            $minutes += array_key_exists($dayNum, $this->vacations[$this->date->getYear()] ?? [])
                ? $this->vacations[$this->date->getYear()][$dayNum]
                : self::DAY_TYPES[$workDays[$dayNum]];
        }

        return $minutes;
    }

    private function getWorkDays(): ?string
    {
        if (isset($this->workDays)) {
            return $this->workDays;
        }

        $result = $this->load();

        return $this->workDays = $result;
    }

    private function load(): ?string
    {
        $year = $this->date->format('Y');

        if ($result = $this->cache->get($year)) {
            return $result;
        }

        try {
            $result = $this->httpClient->get(
                '/api/getdata',
                [
                    'query' => [
                        'year' => $year,
                        'pre' => 1,
                        'covid' => 1,
                    ],
                ]
            );

            $result = $result->getBody();
        } catch (HttpClientException $e) {
            exit("Production Calendar API Error: {$e->getMessage()}" . PHP_EOL);
        }

        $this->cache->set($year, $result);

        return $result;
    }
}
