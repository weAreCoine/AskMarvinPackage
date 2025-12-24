<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\TraceContext;

use Carbon\Carbon;
use Marvin\Ask\Abstracts\AbstractObservation;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\ObservationLevel;
use Marvin\Ask\Enums\TraceEventType;

class Generation extends AbstractObservation
{
    public string $model;
    public string $statusMessage;
    public array $modelParameters = [];
    public array $usageDetails = [];
    public ?PromptTemplate $prompt;
    public Carbon $completionStartTime;
    public ?string $input = null;

    public TraceEventType $eventType = TraceEventType::GENERATION {
        get => $this->eventType;
    }

    public function __construct(
        string           $name,
        string           $traceId,
        string           $model,
        string           $statusMessage,
        Carbon           $completionStartTime,
        ?PromptTemplate  $prompt = null,
        ?string          $parentId = null,
        array            $modelParameters = [],
        array            $usageDetails = [],
        ?string          $input = null,
        ?string          $output = null,
        ?array           $metadata = [],
        ?string          $version = null,
        ?Carbon          $startTime = null,
        ?Carbon          $endTime = null,
        ?string          $environment = null,
        ObservationLevel $level = ObservationLevel::DEFAULT,
    )
    {
        $this->model = $model;
        $this->statusMessage = $statusMessage;
        $this->modelParameters = $modelParameters;
        $this->usageDetails = $usageDetails;
        $this->prompt = $prompt;
        $this->completionStartTime = $completionStartTime;

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
            level: $level,
        );
    }

}
