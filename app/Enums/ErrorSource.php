<?php

namespace App\Enums;

enum ErrorSource: string
{
    case Updater = 'updater';
    case HealthCheck = 'health_check';
    case Agent = 'agent';
    case WpError = 'wp_error';
    case Fatal = 'fatal';
}
