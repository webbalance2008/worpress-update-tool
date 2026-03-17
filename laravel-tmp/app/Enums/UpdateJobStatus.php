<?php

namespace App\Enums;

enum UpdateJobStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case PartiallyFailed = 'partially_failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::PartiallyFailed => 'Partially Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::Failed => 'red',
            self::PartiallyFailed => 'orange',
        };
    }
}
