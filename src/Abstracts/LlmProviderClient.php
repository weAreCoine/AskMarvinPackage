<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Generator;
use Marvin\Ask\Contracts\LlmClientContract;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\Locale;
use Marvin\Ask\Models\Chat;
use Prism\Prism\Enums\Provider;

abstract class LlmProviderClient implements LlmClientContract
{
    public Provider $chatProvider;

    public Provider $embedProvider;

    public Provider $speechToTextProvider;

    public Provider $textToSpeechProvider;

    public string $embedModel;

    public string $chatModel;

    public string $speechToTextModel;

    public string $textToSpeechModel;

    public string $lowDifficultyChatModel;

    public Provider $lowDifficultyChatProvider;

    abstract public function generateConversation(
        PromptTemplate|string $prompt,
        ?Chat $chat = null,
        array $retrievedContents = []
    ): array;

    abstract public function text(
        PromptTemplate|string $prompt,
        ?Chat $chat = null,
        array $retrievedContents = [],
        bool $stream = true,
        bool $forceNotStructuredOutput = false,
        Locale $locale = Locale::ITALIAN,
        bool $isLowDifficultyTask = false
    ): string|Generator;

    abstract public function embed(string $prompt): array;

    abstract public function conversationFromChat(?Chat $chat): array;

    abstract public function speechToText(string $audioPath): string;

    /**
     * Method used to generate structured output from the given prompt.
     * It is up to the concrete implementation to decide how and when to generate the structured output.
     */
    abstract protected function structured(
        PromptTemplate $prompt,
        ?Chat $chat = null,
        array $retrievedContents = [],
        Locale $locale = Locale::ITALIAN,
        bool $isLowDifficultyTask = false
    ): string;
}
