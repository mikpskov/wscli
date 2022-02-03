<?php

declare(strict_types=1);

namespace App\Calendar;

use App\Utils\Date;

abstract class Calendar
{
    protected Date $date;

    /** @var array<int, int[]> */
    protected array $vacations;

    public function __construct(array $config, ?string $date = null)
    {
        $this->setDate($date);
        $this->setVacations($config['vacations'] ?? []);
    }

    public function setDate(?string $date = null): void
    {
        $this->date = new Date($date);
    }

    public function setVacations(array $vacations): void
    {
        $this->vacations = [];

        foreach ($vacations as $day => $hours) {
            if (!$date = new Date($day)) {
                continue;
            }

            $this->vacations[$date->getYear()][$date->daysFromStartOfYear()] = Date::hoursToMinutes($hours);
        }
    }

    abstract public function getForDay(): int;

    abstract public function getForMonth(bool $perMonth = false): int;
}
