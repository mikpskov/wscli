<?php

declare(strict_types=1);

namespace App\Tracker;

use App\HttpClient\Exceptions\HttpClientException;
use App\HttpClient\HttpClient;
use App\Utils\Date;
use SimpleXMLElement;

final class Worksnaps implements Tracker
{
    private const BASE_URL = 'https://api.worksnaps.com';

    private int $project;

    private int $user;

    private Date $date;

    private HttpClient $httpClient;

    public function __construct(array $config, ?string $date = null)
    {
        $this->project = $config['project'];
        $this->user = $config['user'];
        $this->setDate($date);

        $this->httpClient = new HttpClient([
            'base_uri' => self::BASE_URL,
            'auth' => [$config['token'], 'ignored'],
            'proxy' => $config['proxy'] ?? null,
        ]);
    }

    public function setDate(?string $date = null): void
    {
        $this->date = new Date($date);
    }

    public function getForMonth(): int
    {
        return $this->getReport(
            $this->date->startOfMonth()->getTimestamp(),
            $this->date->endOfDay()->getTimestamp()
        );
    }

    public function getForDay(): int
    {
        return $this->getReport(
            $this->date->startOfDay()->getTimestamp(),
            $this->date->endOfDay()->getTimestamp()
        );
    }

    private function getReport(int $from, int $to): ?int
    {
        try {
            $result = $this->httpClient->get(
                "/api/projects/$this->project/reports",
                [
                    'query' => [
                        'name' => 'time_summary',
                        'user_ids' => $this->user,
                        'from_timestamp' => $from,
                        'to_timestamp' => $to,
                    ],
                ]
            );

            $result = $result->getBody();
        } catch (HttpClientException $e) {
            exit("Worksnaps API Error: {$e->getMessage()}" . PHP_EOL);
        }

        $sum = 0;
        $summary = new SimpleXMLElement($result);
        foreach ($summary as $entry) {
            $sum += (int)$entry->duration_in_minutes;
        }

        return $sum;
    }
}
