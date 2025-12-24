<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\TraceContext;

use Illuminate\Support\Carbon;
use Marvin\Ask\Abstracts\AbstractObservation;
use Marvin\Ask\Enums\TraceEventType;

class Event extends AbstractObservation
{
    public Carbon $timestamp {
        get => $this->startTime;
        set {
            $this->startTime = $value;
        }
    }

    public TraceEventType $eventType = TraceEventType::EVENT {
        get => $this->eventType;
    }

}
