<?php

declare(strict_types=1);

namespace Marvin\Ask\Clients;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Log;
use Marvin\Ask\Abstracts\AbstractTracingClient;
use Marvin\Ask\DataTransferObjects\Langfuse\LangfuseHealth;
use Marvin\Ask\DataTransferObjects\Langfuse\LangfusePrompt;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Entities\TraceContext\Event;
use Marvin\Ask\Entities\TraceContext\Generation;
use Marvin\Ask\Entities\TraceContext\Span;
use Marvin\Ask\Entities\TraceContext\TracingContext;
use Marvin\Ask\Enums\DurationInSeconds;
use Marvin\Ask\Enums\TraceEventType;
use Marvin\Ask\Handlers\ExceptionsHandler;

final class LangfuseClient extends AbstractTracingClient
{
    protected PendingRequest $http;

    public function __construct(
        protected readonly string $publicKey,
        protected readonly string $secretKey,
        protected string $baseUrl
    ) {
        if (str_ends_with($this->baseUrl, '/')) {
            $this->baseUrl = substr(
                $this->baseUrl,
                0,
                strlen($this->baseUrl) - 1
            );
        }

        $this->http = Http::baseUrl($this->baseUrl.'/api/public')
            ->withBasicAuth($this->publicKey, $this->secretKey)
            ->acceptJson()
            ->asJson();
    }

    public function health(): LangfuseHealth|false
    {
        try {
            return LangfuseHealth::fromArray(
                $this->http->get('/health')->json()
            );
        } catch (ConnectionException $e) {
            Log::error($e->getMessage(), $e->getTrace());

            return false;
        }
    }

    public function getTrace(string $id): array|false
    {
        try {
            return $this->http->get(
                sprintf('/traces/%s', $id)
            )->json();
        } catch (ConnectionException $e) {
            Log::error('Langfuse connection error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'traceId' => $id,
            ]);

            return false;
        }
    }

    public function getPrompt(
        string $promptName,
        ?string $label = null,
        ?int $version = null
    ): LangfusePrompt|false {
        if (($version === null && $label === null)
            || ($version !== null && $label !== null)
        ) {
            throw new InvalidArgumentException(
                'Either version or label must be provided'

            );
        }

        $key = $version !== null ? 'version' : 'label';
        $value = $version ?? $label;

        try {
            $response = cache()->remember(
                sprintf(
                    'prompt_%s_%s_%s',
                    Str::slug($promptName),
                    $key,
                    Str::slug((string) $value)
                ),
                DurationInSeconds::day(),
                fn (): array => $this->http
                    ->get(
                        sprintf(
                            '/v2/prompts/%s?%s=%s',
                            $promptName,
                            $key,
                            $value
                        )
                    )
                    ->json()
            );

            if (array_key_exists('error', $response)) {
                throw new InvalidArgumentException('Prompt not found: '.$response['error'].'.');
            }

            return LangfusePrompt::fromArray(
                $response
            );
        } catch (ConnectionException $e) {
            $value = $version ?? $label;
            ExceptionsHandler::handle($e, [
                'trace' => $e->getTrace(),
                'promptName' => $promptName,
                'key' => $key,
                'value' => $value,
            ]);

            return false;
        }
    }

    public function ingest(TracingContext $tracingContext): bool
    {
        try {
            $payload = $this->observationContextPayload($tracingContext);
            if (empty($payload)) {
                throw new InvalidArgumentException('Empty tracing context');
            }
            $response = $this->http->post(
                '/ingestion',
                ['batch' => $payload]
            );

            return match ($response->status()) {
                207 => $this->handleMultiStatus($response),
                200 => true,
                400, 401, 403, 404, 405 => $this->handleHttpError($response),
                default => false,
            };
        } catch (ConnectionException|Exception $e) {
            ExceptionsHandler::handle($e);

            return false;
        }
    }

    /**
     * This method is used here to create a payload for ingestion. It is not placed inside Entities in order to avoid
     * coupling the Entities with the Langfuse client.
     *
     * @throws Exception
     */
    protected function observationContextPayload(TracingContext $tracingContext): array
    {
        if ($tracingContext->hasOpenTraces()) {
            throw new Exception('There are still open traces.');
        }

        return [
            [
                'id' => $tracingContext->traceId,
                'timestamp' => $tracingContext->timestamp->format('Y-m-d\TH:i:s.v\Z'),
                'type' => TraceEventType::TRACE->value,
                'body' => [
                    'id' => $tracingContext->traceId,
                    'environment' => config('app.env'),
                    'name' => $tracingContext->name,
                    'userId' => ! empty($tracingContext->userId) ? sprintf('%s-%d', config('app.name'),
                        $tracingContext->userId) : null,
                    'input' => $tracingContext->stack->whereNotNull('input')?->first()->input ?? null,
                    'output' => $tracingContext->stack->whereNotNull('output')?->last()->output ?? null,
                    'sessionId' => $tracingContext->sessionId,
                    'version' => (string) config('app.version'),
                    'metadata' => [
                        'source' => 'marvin',
                        'device' => 'web',
                    ],
                    'public' => true,
                ],
            ],
            ...$tracingContext->stack->map(
                fn (Span|Generation|Event $observation
                ) => $this->getPayloadForObservation($observation)
            )->toArray(),
        ];
    }

    public function getPayloadForObservation(Span|Generation|Event $observation): array
    {
        if (! $observation instanceof Event) {
            $specificBodyFields = array_merge(
                ($observation instanceof Span ? $this->getSpanPayloadBodyFields($observation) : $this->getGenerationPayloadBodyFields($observation)),
                $this->getObservationPromptPayloadBodyFields($observation->prompt)
            );
        }

        $timestamps = $observation instanceof Event ? [
            'timestamp' => $observation->timestamp->format('Y-m-d\TH:i:s.u\Z'),
        ] : [
            'startTime' => $observation->startTime->format('Y-m-d\TH:i:s.u\Z'),
            'endTime' => $observation->endTime->format('Y-m-d\TH:i:s.u\Z'),
        ];

        return [
            'id' => $observation->id,
            'type' => $observation->eventType->value,
            'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.u\Z'),
            'body' => [
                'id' => $observation->id,
                'name' => $observation->name,
                'traceId' => $observation->traceId,
                'environment' => $observation->environment,
                'parentObservationId' => $observation->parentId,
                'level' => $observation->level->value,
                'input' => $observation->input,
                'output' => $observation->output,
                'metadata' => $observation->metadata,
                ...($specificBodyFields ?? []),
                ...$timestamps,
            ],
        ];
    }

    protected function getSpanPayloadBodyFields(Span $span): array
    {
        return [];
    }

    protected function getGenerationPayloadBodyFields(Generation $generation): array
    {
        return [
            'model' => $generation->model,
            'modelParameters' => ! empty($generation->modelParameters) ?
                $generation->modelParameters :
                $generation->prompt?->config->toArray() ?? [],
            'usage' => ! empty($generation->usageDetails) ? $generation->usageDetails : null,
            'version' => $generation->version,
        ];
    }

    protected function getObservationPromptPayloadBodyFields(?PromptTemplate $promptTemplate): array
    {
        if ($promptTemplate === null) {
            return [];
        }

        return [
            'promptName' => $promptTemplate->name,
            'promptVersion' => $promptTemplate->version,
            'promptId' => $promptTemplate->id,
        ];
    }

    protected function handleMultiStatus(Response $response): bool
    {
        $successes = $response->json('successes', []);
        $errors = $response->json('errors', []);

        foreach ($successes as $success) {
            if (app()->environment('local')) {
                Log::debug('Langfuse success:', [
                    'id' => $success['id'],
                    'status' => $success['status'],
                ]);
            }
        }

        foreach ($errors as $error) {
            Log::error('Langfuse error:', [
                'id' => $error['id'],
                'status' => $error['status'],
                'message' => $error['message'] ?? null,
            ]);
        }

        return empty($errors);
    }

    protected function handleHttpError(Response $response): bool
    {
        Log::error('Langfuse ingestion HTTP error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    public function getObservation(string $id): array|false
    {
        try {
            return $this->http->get(
                sprintf('/v2/observations/%s', $id)
            )->json();
        } catch (ConnectionException $e) {
            Log::error('Langfuse connection error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'observationId' => $id,
            ]);

            return false;
        }
    }

    public function deleteTrace(string $id): bool
    {
        try {
            $response = $this->http->delete(sprintf('/traces/%s', $id));

            return $response->status() === 200
                && $response->json()['message']
                === 'Trace deleted successfully';
        } catch (ConnectionException $e) {
            Log::error('Langfuse connection error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'traceId' => $id,
            ]);

            return false;
        }
    }

    public function flushTraces(array|string $traceIds = []): bool
    {
        if (empty($traceIds)) {
            $traceIds = array_map(fn (array $trace) => $trace['id'],
                $this->listTraces());
        } elseif (is_string($traceIds)) {
            $traceIds = [$traceIds];
        }

        if (empty($traceIds)) {
            throw new InvalidArgumentException('Empty trace ids');
        }
        try {
            $response = $this->http->delete('/traces', [
                'traceIds' => $traceIds,
            ]);
            if ($response->status() !== 200) {
                Log::error('Langfuse flush traces error: '.$response->body(), [
                    'trace' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException $e) {
            Log::error('Langfuse connection error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'traceIds' => $traceIds,
            ]);

            return false;
        }
    }

    public function listTraces(): array|false
    {
        try {
            $response = $this->http->get('/traces');

            return $response->json()['data'] ?? false;
        } catch (ConnectionException $e) {
            Log::error('Langfuse connection error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
