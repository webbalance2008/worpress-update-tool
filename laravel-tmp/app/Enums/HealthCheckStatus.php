<?php

namespace App\Enums;

enum HealthCheckStatus: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Degraded = 'degraded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Passed => 'Passed',
            self::Degraded => 'Degraded',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Passed => 'green',
            self::Degraded => 'orange',
            self::Failed => 'red',
        };
    }
}
