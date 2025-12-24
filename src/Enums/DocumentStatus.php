<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

use Marvin\Ask\Traits\EnumsUtilities;

enum DocumentStatus: string
{
    use EnumsUtilities;

    case UPLOADED = 'uploaded';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
}
