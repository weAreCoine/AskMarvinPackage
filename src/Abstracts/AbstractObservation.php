<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Marvin\Ask\Enums\ObservationLevel;
use Marvin\Ask\Enums\TraceEventType;

abstract class AbstractObservation
{
    public readonly string $id;

    protected TraceEventType $eventType;

    public function __construct(
        public string $name,
        public string $traceId,
        public ?string $parentId = null,
        public ?string $input = null,
        public ?string $output = null,
        public ?array $metadata = [],
        public ?string $version = null,
        public ?Carbon $startTime = null,
        public ?Carbon $endTime = null,
        public ?string $environment = null,
        public ObservationLevel $level = ObservationLevel::DEFAULT,
    ) {
        $this->id = (string) Str::uuid();
        $this->version ??= config('app.version');
        $this->environment ??= config('app.env');
    }

    public function finish(?string $output = null): static
    {
        $this->endTime = Carbon::now();
        $this->output = $output;

        return $this;
    }
}
