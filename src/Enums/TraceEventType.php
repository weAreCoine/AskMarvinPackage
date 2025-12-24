<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

enum TraceEventType: string
{
    case TRACE = 'trace-create';
    case SPAN = 'span-create';
    case GENERATION = 'generation-create';
    case EVENT = 'event-create';
}
