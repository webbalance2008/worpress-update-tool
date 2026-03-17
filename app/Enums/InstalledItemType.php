<?php

namespace App\Enums;

enum InstalledItemType: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';
    case Core = 'core';

    public function label(): string
    {
        return match ($this) {
            self::Plugin => 'Plugin',
            self::Theme => 'Theme',
            self::Core => 'WordPress Core',
        };
    }
}
