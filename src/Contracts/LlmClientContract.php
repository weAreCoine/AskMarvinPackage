<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

use Generator;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\Locale;
use Marvin\Ask\Models\Chat;

interface LlmClientContract
{
    /**
     * Embeds the provided prompt into an array representation.
     *
     * @param  string  $prompt  The input string to be embedded.
     * @return float[] The resulting array representation of the prompt.
     */
    public function embed(string $prompt): array;

    /**
     * Handles the process of generating a text-based response using the given prompt and optional chat context.
     *
     * @param  PromptTemplate|string  $prompt  The prompt containing configuration and settings for text generation.
     * @param  Chat|null  $chat  An optional chat instance to maintain conversational context.
     * @param  array  $retrievedContents  Additional contents that can augment the prompt's conversation.
     * @param  bool  $stream  Whether to return the response as a streamed output.
     * @param  bool  $forceNotStructuredOutput  Whether to force the response to be non-structured. Default is false.
     * @return string|Generator A Generator for streamed output or a Response containing the complete result.
     */
    public function text(
        PromptTemplate|string $prompt,
        ?Chat $chat = null,
        array $retrievedContents = [],
        bool $stream = true,
        bool $forceNotStructuredOutput = false,
        Locale $locale = Locale::ITALIAN,
        bool $isLowDifficultyTask = false
    ): string|Generator;

    /**
     * Generates a conversation array based on the provided prompt, optional chat history,
     * and additional retrieved contents.
     *
     * @param  PromptTemplate|string  $prompt  The prompt containing system and user messages.
     * @param  Chat|null  $chat  Optional chat object to preserve chat history.
     * @param  array  $retrievedContents  Additional content retrieved to include in the conversation.
     * @return array The generated conversation containing system and user messages.
     */
    public function generateConversation(
        PromptTemplate|string $prompt,
        ?Chat $chat = null,
        array $retrievedContents = []
    ): array;

    /**
     * Converts a given Chat instance into an array of messages.
     *
     * If the provided Chat instance is null, an empty array is returned.
     * Each message in the Chat is mapped to either a UserMessage or an AssistantMessage
     * based on its type.
     *
     * @param  Chat|null  $chat  An optional Chat instance containing messages.
     * @return array An array of mapped messages from the Chat instance.
     */
    public function conversationFromChat(?Chat $chat): array;

    /**
     * Converts the provided audio file into its text transcription.
     *
     * Note that this method does not involve the chat/conversation context while it is only used for audio transcription.
     *
     * @param  string  $audioPath  The file local path to the audio file.
     * @return string The transcribed text from the audio.
     */
    public function speechToText(string $audioPath): string;
}
