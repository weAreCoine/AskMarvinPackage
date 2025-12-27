<?php

declare(strict_types=1);

namespace Marvin\Ask\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Marvin\Ask\Abstracts\AbstractTracingClient;
use Marvin\Ask\Clients\LangfuseClient;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Entities\TraceContext\Event;
use Marvin\Ask\Entities\TraceContext\Generation;
use Marvin\Ask\Entities\TraceContext\Span;
use Marvin\Ask\Entities\TraceContext\TracingContext;
use Marvin\Ask\Enums\ObservationLevel;
use Marvin\Ask\Handlers\ExceptionsHandler;

class TracingContextService
{
    public TracingContext $context {
        get => $this->context;
    }

    /**
     * @param  LangfuseClient  $client
     */
    public function __construct(public AbstractTracingClient $client)
    {
        $this->context = new TracingContext;
    }

    public function newTrace(string $name = 'marvin_trace_'): TracingContextService
    {
        $this->context->traceId = uniqid($name);
        $this->context->name = $this->context->traceId;
        $this->context->stack = collect();
        $this->context->sessionId = session()->getId();
        $this->context->userId = auth()->id();
        $this->context->timestamp = Carbon::now();

        return $this;
    }

    public function pushEvent(
        string $name,
        string|array|null $input = null,
        string|array|null $error = null,
        ?array $metadata = [],
        ObservationLevel $level = ObservationLevel::DEFAULT,
    ): TracingContextService {
        $parent = $this->context->getLastOpened();
        $event = new Event(
            name: $name,
            traceId: $this->context->traceId,
            parentId: $parent?->id,
            input: $input,
            output: $error,
            metadata: $metadata,
            startTime: Carbon::now(),
            environment: config('app.env'),
            level: $level,
        );
        $this->context->stack->push($event);

        return $this;
    }

    public function beginSpan(
        string $name,
        string|array|null $input = null,
        ?array $metadata = [],
        ObservationLevel $level = ObservationLevel::DEFAULT,
        ?PromptTemplate $prompt = null,
    ): TracingContextService {
        $parent = $this->context->getLastOpenedSpan();
        if (is_array($input)) {
            $input = json_encode($input);
        }
        $span = new Span(
            name: $name,
            traceId: $this->context->traceId,
            parentId: $parent?->id,
            input: $input,
            metadata: $metadata,
            startTime: Carbon::now(),
            environment: config('app.env'),
            level: $level,
            prompt: $prompt,
        );

        $this->context->stack->push($span);

        return $this;
    }

    public function beginGeneration(
        string $name,
        ?PromptTemplate $prompt = null,
        string $statusMessage = 'Generation started',
        ?string $input = null,
        ?string $model = null,
        array $modelParameters = [],
        array $usageDetails = [],
        array $metadata = [],
    ): TracingContextService {
        $parent = $this->context->getLastOpenedSpan();

        $generation = new Generation(
            name: $name,
            traceId: $this->context->traceId,
            model: $model ?? config('ask.services.prism.chat.model'),
            statusMessage: $statusMessage,
            completionStartTime: Carbon::now(),
            prompt: $prompt,
            parentId: $parent?->id,
            modelParameters: $modelParameters,
            usageDetails: $usageDetails,
            input: $input,
            metadata: $metadata,
            startTime: Carbon::now(),
        );

        $this->context->stack->push($generation);

        return $this;
    }

    public function closeGeneration(mixed $output): ?TracingContextService
    {
        $generation = $this->context->getLastOpenedGeneration();

        if ($generation === null) {
            Log::error('Tried to close a generation that was not open.');

            return null;
        }
        if (! is_string($output)) {
            $output = json_encode($output, JSON_PRETTY_PRINT);
        }

        $generation->finish($output);

        return $this;
    }

    public function closeSpan(Collection|array|string|null $output = null, array $metadata = []): ?TracingContextService
    {
        $span = $this->context->getLastOpenedSpan();
        if ($output instanceof Collection) {
            $output = $output->toArray();
        }

        if (is_array($output)) {
            $output = json_encode($output);
        }

        if ($span === null) {
            Log::alert('Tried to close a span that was not open.');

            return null;
        }

        $span->metadata = array_merge($span->metadata, $metadata);

        $span->finish($output);

        return $this;
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function isEmpty(): bool
    {
        return $this->context->isEmpty();
    }

    public function ingest(): bool
    {
        // Don't ingest traces generated by tests
        if (str_contains($this->context->name, 'test')) {
            return true;
        }

        try {
            if ($this->client->ingest($this->context)) {
                $this->context = new TracingContext;

                return true;
            }

            return false;
        } catch (Exception $e) {
            ExceptionsHandler::handle($e);

            return false;
        }
    }
}
