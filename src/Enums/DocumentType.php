<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

use Marvin\Ask\Traits\EnumsUtilities;

enum DocumentType: string
{
    use EnumsUtilities;

    case STATUTE = 'statute';
    case RESOLUTION = 'resolution';
    case OTHER = 'other';
}
