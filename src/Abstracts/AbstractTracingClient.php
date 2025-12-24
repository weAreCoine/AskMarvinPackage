<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Marvin\Ask\Contracts\TracingClientContract;
use Marvin\Ask\Entities\TraceContext\TracingContext;

abstract class AbstractTracingClient implements TracingClientContract
{
    abstract public function health(): object|false;

    abstract public function getTrace(string $id): array|object|false;

    abstract public function getPrompt(
        string $promptName,
        ?string $label = null,
        ?int $version = null
    ): object|false;

    abstract public function ingest(TracingContext $tracingContext): bool;

    abstract public function deleteTrace(string $id): bool;

    abstract public function flushTraces(array|string $traceIds = []): bool;

    abstract public function listTraces(): array|false;
}
