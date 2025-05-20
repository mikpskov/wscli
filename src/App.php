<?php

declare(strict_types=1);

namespace App;

use App\Calendar\Calendar;
use App\Enums\Period;
use App\Tracker\Tracker;
use App\Utils\Date;
use App\Tracker\Worksnaps;
use App\Calendar\IsDayOff;
use App\ValueObjects\ReportEntry;

final class App
{
    private Calendar $calendar;

    private Tracker $tracker;

    private bool $inHours;

    public function __construct(array $config, ?string $date = null)
    {
        $this->calendar = new IsDayOff($config, $date);
        $this->tracker = new Worksnaps($config, $date);
        $this->inHours = $config['inHours'];
    }

    public function day(): string
    {
        $required = $this->calendar->getForDay();
        $worked = $this->tracker->getSumForDay();

        return $this->getString(Period::Day, $worked, $worked->duration - $required);
    }

    public function month(): string
    {
        $required = $this->calendar->getForMonth();
        $worked = $this->tracker->getSumForMonth();

        return $this->getString(Period::Month, $worked, $worked->duration - $required);
    }

    private function getString(Period $period, ReportEntry $reportEntry, int $overtime): string
    {
        $timeString = $this->inHours
            ? Date::minutesToHours($reportEntry->duration)
            : Date::minutesToHoursSeparately($reportEntry->duration);

        $overtimeString = $this->inHours
            ? Date::minutesToHours($overtime, withSign: true)
            : Date::minutesToHoursSeparately($overtime, withSign: true);

        return "{$period->getLabel()}:\t{$timeString} " .
            '(' . ($overtime >= 0 ? "\e[92m" : "\e[91m") . "{$overtimeString}\e[0m) " .
            number_format($reportEntry->activity, 2);
    }
}
