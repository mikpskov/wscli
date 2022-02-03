<?php

declare(strict_types=1);

namespace App;

use App\Calendar\Calendar;
use App\Tracker\Tracker;
use App\Utils\Date;
use App\Tracker\Worksnaps;
use App\Calendar\IsDayOff;

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
        $requiredDay = $this->calendar->getForDay();
        $workedToday = $this->tracker->getSumForDay();
        $dayOvertime = $workedToday - $requiredDay;

        $function = $this->inHours
            ? 'minutesToHours'
            : 'minutesToHoursSeparately';

        return "day:\t" . Date::$function($workedToday) .
            ' (' . ($dayOvertime > 0 ? "\e[92m" : "\e[91m") .
            Date::$function($dayOvertime, true) . "\e[0m)";
    }

    public function month(): string
    {
        $requiredMonth = $this->calendar->getForMonth();
        $workedMonth = $this->tracker->getSumForMonth();
        $monthOvertime = $workedMonth - $requiredMonth;

        $function = $this->inHours
            ? 'minutesToHours'
            : 'minutesToHoursSeparately';

        return "month:\t" . Date::$function($workedMonth) .
            ' (' . ($monthOvertime > 0 ? "\e[92m" : "\e[91m") .
            Date::$function($monthOvertime, true) . "\e[0m)";
    }
}
