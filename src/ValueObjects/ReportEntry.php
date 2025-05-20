<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class ReportEntry
{
    public function __construct(
        public int $duration,
        public float $activity,
    ) {
    }
}
