<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

enum ObservationLevel: string
{
    case DEFAULT = 'DEFAULT';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
}
