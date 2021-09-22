<?php

declare(strict_types=1);

namespace App\Utils;

use DateTimeImmutable;
use DateTimeZone;

final class Date extends DateTimeImmutable
{
    public function __construct(?string $time = null, ?DateTimeZone $timezone = null)
    {
        if (!$timezone) {
            $timezone = new DateTimeZone('UTC');
        }

        parent::__construct($time ?: 'now', $timezone);
    }

    public function startOfYear(): self
    {
        return $this
            ->setDate($this->getYear(), 1, 1)
            ->startOfDay();
    }

    public function endOfYear(): self
    {
        return $this
            ->setDate($this->getYear(), 12, 31)
            ->endOfDay();
    }

    public function startOfMonth(): self
    {
        return $this
            ->modify('first day of this month')
            ->startOfDay();
    }

    public function endOfMonth(): self
    {
        return $this
            ->modify('last day of this month')
            ->endOfDay();
    }

    public function startOfDay(): self
    {
        return $this
            ->setTime(0, 0);
    }

    public function endOfDay(): self
    {
        return $this
            ->setTime(23, 59, 59);
    }

    public function getYear(): int
    {
        return (int)$this
            ->format('Y');
    }

    public function daysFromStartOfYear(): int
    {
        return (int)$this
            ->startOfYear()
            ->diff($this, true)
            ->format('%a');
    }

    public static function minutesToHours(int $minutes, bool $withSign = false): string
    {
        $sign = $withSign && $minutes > 0 ? '+' : '';

        return $sign . round($minutes / 60, 2);
    }

    public static function hoursToMinutes(float $hours): int
    {
        return (int)round($hours * 60);
    }

    public static function minutesToHoursSeparately(int $minutes, bool $withSign = false): string
    {
        $sign = $minutes > 0 ? '+' : '-';
        $minutes = (int)abs($minutes);

        return sprintf(
            '%s%02d:%02d',
            $withSign ? $sign : '',
            floor($minutes / 60),
            $minutes % 60,
        );
    }
}
