<?php

declare(strict_types=1);

namespace App\Tracker;

interface Tracker
{
    public function setDate(?string $date = null): void;

    public function getForMonth(): int;

    public function getForDay(): int;
}
