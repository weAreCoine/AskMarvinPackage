<?php

namespace Marvin\Ask\Facades;

use Faker\Generator;
use Illuminate\Support\Facades\Facade;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\Locale;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Services\LlmService;
use Marvin\Ask\Services\VectorialDatabaseService;

/**
 * @see \Marvin\Ask\Ask
 */
class Ask extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Marvin\Ask\Ask::class;
    }

    /**
     * Processes the given prompt and returns the generated response or a streamable output.
     *
     * @param PromptTemplate|string $prompt The prompt template or string to generate a response for.
     * @param Chat|null $chat Optional chat context for the prompt.
     * @param array<int,mixed> $retrievedContents Additional retrieved content to provide context for generation.
     * @param bool $stream Whether to stream the generated response or return it as a single string.
     * @param Locale $locale The locale to use for generation, defaulting to Italian.
     * @param bool $isLowDifficultyTask Whether the task is categorized as low difficulty.
     * @return Generator|string A generator for streamed output if $stream is true, otherwise a single response string.
     */
    public function question(
        PromptTemplate|string $prompt,
        ?Chat                 $chat = null,
        array                 $retrievedContents = [],
        bool                  $stream = true,
        Locale                $locale = Locale::ITALIAN,
        bool                  $isLowDifficultyTask = false
    ): Generator|string
    {
        return $this->llm()->text(
            $prompt,
            $chat,
            $retrievedContents,
            $stream,
            $locale,
            $isLowDifficultyTask
        );
    }

    public function llm(): LlmService
    {
        return app(LlmService::class);
    }

    /**
     * Embeds the given prompt(s) into a vector representation.
     *
     * @param string|array<int,string> $prompt The prompt or an array of prompts to embed.
     * @param string ...$prompts Additional prompts to embed.
     * @return array<int,float>|array<int,array<int,float>> The vector representation of the prompxsdft(s).
     */
    public function embed(string|array $prompt, string ...$prompts): array
    {
        $prompts = is_string($prompt) ? [$prompt, ...$prompts] : [...$prompt, ...$prompts];

        return $this->llm()->embed($prompts);
    }

    public function vectorialDatabase(): VectorialDatabaseService
    {
        return app(VectorialDatabaseService::class);
    }
}
