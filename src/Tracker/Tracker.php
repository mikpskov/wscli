<?php

declare(strict_types=1);

namespace App\Tracker;

use App\ValueObjects\ReportEntry;

interface Tracker
{
    public function setDate(?string $date = null): void;

    public function getSumForMonth(): ReportEntry;

    public function getSumForDay(): ReportEntry;
}
