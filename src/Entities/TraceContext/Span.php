<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\TraceContext;

use Carbon\Carbon;
use Marvin\Ask\Abstracts\AbstractObservation;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\ObservationLevel;
use Marvin\Ask\Enums\TraceEventType;

class Span extends AbstractObservation
{
    public ObservationLevel $level;
    public ?PromptTemplate $prompt;
    public TraceEventType $eventType = TraceEventType::SPAN {
        get => $this->eventType;
    }

    public function __construct(
        string           $name,
        string           $traceId,
        ?string          $parentId = null,
        ?string          $input = null,
        ?string          $output = null,
        ?array           $metadata = [],
        ?string          $version = null,
        ?Carbon          $startTime = null,
        ?Carbon          $endTime = null,
        ?string          $environment = null,
        ObservationLevel $level = ObservationLevel::DEFAULT,
        ?PromptTemplate  $prompt = null
    )
    {
        $this->prompt = $prompt;
        parent::__construct(
            name: $name,
            traceId: $traceId,
            parentId: $parentId,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            startTime: $startTime,
            endTime: $endTime,
            environment: $environment,
            level: $level
        );
    }
}
