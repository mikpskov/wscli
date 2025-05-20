<?php

declare(strict_types=1);

namespace App\Enums;

enum Period
{
    case Day;
    case Month;

    public function getLabel(): string
    {
        return match ($this) {
            self::Day => 'day',
            self::Month => 'month',
        };
    }
}
