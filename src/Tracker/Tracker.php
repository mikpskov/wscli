<?php

declare(strict_types=1);

namespace App\Tracker;

interface Tracker
{
    public function setDate(?string $date = null): void;

    public function getSumForMonth(): int;

    public function getSumForDay(): int;
}
