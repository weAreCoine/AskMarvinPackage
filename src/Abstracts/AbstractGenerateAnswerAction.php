<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Exception;
use Marvin\Ask\Contracts\ControllerActionContract;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Entities\VectorialDB\SearchMatch;
use Marvin\Ask\Repositories\PromptRepository;
use Marvin\Ask\Services\LlmService;
use Marvin\Ask\Services\TracingContextService;
use Marvin\Ask\Services\VectorialDatabaseService;

abstract class AbstractGenerateAnswerAction implements ControllerActionContract
{
    protected PromptTemplate|false $prompt;
    protected array $hydration;

    protected string $message;

    public function __construct(
        protected PromptRepository         $promptRepository,
        protected TracingContextService    $trace,
        protected LlmService               $llmService,
        protected VectorialDatabaseService $vectorialDatabaseService,
    )
    {
    }

    /**
     * @template T of self
     * @return T
     */
    public static function make(): static
    {
        return app(static::class);
    }

    abstract public function run();

    /**
     * @throws Exception
     */
    protected function setPrompt(string $promptName, string $label = 'production'): PromptTemplate
    {
        $this->trace->beginSpan(
            'retrieve-prompt',
            input: $this->message,
            metadata: [
                'hydration' => $this->hydration
            ]
        );

        $this->prompt = $this->promptRepository
            ->get($promptName, $label);


        if ($this->prompt === false) {
            $this->trace->closeSpan(sprintf('Failed retrieving prompt %s:%s',
                $promptName,
                $label
            ));

            throw new Exception(sprintf('Prompt %s:%s not found', $promptName, $label));
        }

        $this->prompt->hydrate($this->hydration);

        $this->trace->closeSpan(sprintf('Retrieved prompt ID: %s', $this->prompt->id),
            ['prompt_content' => $this->prompt->prompt]);

        return $this->prompt;
    }

    protected function getSearchResults(): array
    {
        $this->trace->beginSpan('generate-vector', input: $this->message);
        $vector = $this->llmService->embed($this->message);
        $this->trace->closeSpan($vector);

        if (empty($vector)) {
            return [];
        }

        $this->trace->beginSpan('search-vectors', input: $vector);
        $searchResults = $this->vectorialDatabaseService->search($vector[0]);
        $searchResults = $searchResults->map(
            fn(SearchMatch $match) => [
                'content' => $match->content,
                'page_url' => $match->pageUrl,
                'page_title' => $match->pageTitle,
                'updated_at' => $match->updatedAt->format('Y-m-d H:i:s'),
                'score' => $match->score,
            ]
        )->toArray();
        $this->trace->closeSpan(empty($searchResults) ? 'No results found' : $searchResults);

        return $searchResults;
    }

    protected function beginGeneration(array $metadata = []): void
    {
        $this->trace->beginGeneration(
            name: 'generate-response',
            prompt: $this->prompt,
            input: $this->message,
            modelParameters: $this->prompt->config->toArray(),
            metadata: $metadata
        );
    }

    protected function closeAndIngestGeneration(?string $output = null): void
    {
        $this->trace->closeGeneration($output)->ingest();
    }
}
