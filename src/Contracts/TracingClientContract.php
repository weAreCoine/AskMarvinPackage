<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

use Marvin\Ask\Entities\TraceContext\TracingContext;

interface TracingClientContract
{
    public function health(): object|false;

    public function getTrace(string $id): array|object|false;

    public function getPrompt(
        string  $promptName,
        ?string $label = null,
        ?int    $version = null
    ): object|false;

    public function ingest(TracingContext $tracingContext): bool;

    public function deleteTrace(string $id): bool;

    public function flushTraces(array|string $traceIds = []): bool;

    public function listTraces(): array|false;

}
