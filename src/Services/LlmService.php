<?php

declare(strict_types=1);

namespace Marvin\Ask\Services;

use Generator;
use Marvin\Ask\Abstracts\LlmProviderClient;
use Marvin\Ask\Clients\PrismClient;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\Locale;
use Marvin\Ask\Models\Chat;

/**
 * Represents a service for interacting with the LLM provider.
 * This class provides functionalities for embedding text,
 * generating text responses, and handling conversational data.
 *
 * @property PrismClient $llmClient
 */
final readonly class LlmService
{
    /**
     * @param  PrismClient  $llmClient
     */
    public function __construct(public LlmProviderClient $llmClient) {}

    public function speechToText(string $audioPath): string
    {
        return $this->llmClient->speechToText($audioPath);
    }

    /**
     * @return array<int, array<int, float>>
     */
    public function embed(string|array $prompt, string ...$prompts): array
    {
        $prompts = is_string($prompt) ? [$prompt, ...$prompts] : [...$prompt, ...$prompts];

        return $this->llmClient->multipleEmbed($prompts);
    }

    public function text(
        PromptTemplate|string $prompt,
        ?Chat $chat = null,
        array $retrievedContents = [],
        bool $stream = true,
        Locale $locale = Locale::ITALIAN,
        bool $isLowDifficultyTask = false
    ): string|Generator {
        return $this->llmClient->text($prompt, $chat, $retrievedContents, $stream, locale: $locale, isLowDifficultyTask: $isLowDifficultyTask);
    }

    public function conversationFromChat(?Chat $chat): array
    {
        return $this->llmClient->conversationFromChat($chat);
    }
}
