<?php

declare(strict_types=1);

namespace Marvin\Ask\Actions;

use Exception;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Entities\UserMessageIntent;
use Marvin\Ask\Enums\Locale;
use Marvin\Ask\Enums\MessageType;
use Marvin\Ask\Enums\ObservationLevel;
use Marvin\Ask\Handlers\ExceptionsHandler;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Repositories\PromptRepository;
use Marvin\Ask\Services\ChatService;
use Marvin\Ask\Services\LlmService;
use Marvin\Ask\Services\TracingContextService;
use Marvin\Ask\Services\VectorialDatabaseService;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;

final class GenerateAnswerAction
{
    protected ?Chat $chat = null;

    protected ?int $chatId = null;

    protected bool $streamOutput = false;

    protected bool $canRun = false;

    protected bool $triedToInit = false;

    protected string $message;

    protected array $promptHydrationData = [];

    protected string $traceName;

    protected string $topicExtractorPromptName;

    protected string $answerGenerationPromptName;

    protected Locale $locale;

    protected bool $lowDifficultyTask = false;

    protected false|PromptTemplate $topicExtractorPrompt;

    protected false|PromptTemplate $answerGenerationPrompt;

    /**
     * All the dependencies are injected here, so it's better to use the make method to get an instance.
     */
    public function __construct(
        protected PromptRepository $promptRepository,
        protected TracingContextService $trace,
        protected LlmService $llmService,
        protected VectorialDatabaseService $vectorialDatabaseService,
    ) {
        $this->locale = Locale::from(config('app.locale', 'it'));
    }

    /**
     * @template T of self
     *
     * @return T
     */
    public static function make(): GenerateAnswerAction
    {
        return app(GenerateAnswerAction::class);
    }

    /**
     * @param  array<string, array<string, mixed>>  $promptHydrationData  You can pass an array of hydration data for the prompts.
     *                                                                    These will take precedence over the default values.
     *                                                                    The shape of the array must be: ['prompt_name' => ['placeholder_name' => 'placeholder_value']
     *
     * @throws Exception
     */
    public function init(
        string $answerGenerationPromptName = 'assistant_answer_generation',
        string $topicExtractorPromptName = 'topic_extractor',
        string $traceName = 'marvin_observation_',
        array $promptHydrationData = [],
        bool $lowDifficultyTasks = false
    ): GenerateAnswerAction {
        if ($this->triedToInit) {
            throw new Exception('Cannot init the action twice');
        }

        $this->triedToInit = true;
        $this->lowDifficultyTask = $lowDifficultyTasks;
        $this->promptHydrationData = $promptHydrationData;

        $this->topicExtractorPromptName = $topicExtractorPromptName;
        $this->answerGenerationPromptName = $answerGenerationPromptName;

        $this->traceName = $traceName;

        if ($this->trace->isEmpty()) {
            $this->trace->newTrace($this->traceName);
        }

        if (! $this->setTopicExtractorPrompt() || ! $this->setAnswerGenerationPrompt()) {
            $this->trace->pushEvent(name: 'error',
                error: sprintf(
                    'Failed to retrieve %s or %s prompt',
                    $this->topicExtractorPromptName,
                    $this->answerGenerationPromptName
                ),
                level: ObservationLevel::ERROR,
            );
            $this->trace->ingest();

            return $this;
        }

        $this->canRun = true;

        return $this;
    }

    protected function setTopicExtractorPrompt(): bool
    {
        $this->topicExtractorPrompt = $this->setPrompt($this->topicExtractorPromptName, 'production');

        return $this->topicExtractorPrompt !== false;
    }

    protected function setPrompt(
        string $promptName,
        string $label = 'production',
    ): false|PromptTemplate {
        $promptTraceLabel = str_replace('_', '-', $promptName);

        $this->trace->beginSpan(sprintf('retrieve-%s-prompt', $promptTraceLabel),
            metadata: ['prompt_name' => $promptName, 'label' => $label]);

        $prompt = $this->promptRepository->get($promptName, $label);
        if ($prompt === false) {
            $this->trace->pushEvent(
                name: 'error',
                error: sprintf('Failed to retrieve %s prompt', $promptTraceLabel),
                level: ObservationLevel::ERROR
            );
            $this->trace->closeSpan(sprintf('Failed retrieving %s prompt', $promptTraceLabel));

            return false;
        }

        $this->trace->closeSpan(sprintf('%s prompt retrieved', $promptTraceLabel));

        return $prompt;
    }

    protected function setAnswerGenerationPrompt(): bool
    {
        $this->answerGenerationPrompt = $this->setPrompt($this->answerGenerationPromptName, 'production');

        return $this->answerGenerationPrompt !== false;
    }

    public function streamedOutput(): self
    {
        $this->streamOutput = true;

        return $this;
    }

    public function textOutput(): self
    {
        $this->streamOutput = false;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function run(string $message): false|string|callable
    {
        if (! $this->canRun) {
            throw new Exception($this->triedToInit ? 'There was an error initializing the action. Please try again.' : 'Cannot run the action without initializing it first');
        }

        $this->message = $message;

        $topics = $this->extractTopics();

        if ($topics === false) {
            $this->trace->pushEvent(
                name: 'error',
                input: $this->message,
                error: 'Error extracting topics',
                level: ObservationLevel::ERROR
            );
            $this->trace->ingest();

            return false;
        }

        if (! empty($topics['locale'])) {
            $this->setLocale($topics['locale']);
        }
        $contents = $this->retrieveChunksFromVectorialDatabase($topics);

        return $this->generateAnswer($contents);
    }

    protected function extractTopics(): array|false
    {
        $prompt = clone $this->topicExtractorPrompt;
        $prompt->hydrate($this->generateHydrationData($prompt));

        $this->trace->beginGeneration(
            name: 'topic_extraction',
            prompt: $prompt,
            input: $this->message,
            model: config('ask.services.prism.chat.model'),
            modelParameters: $prompt->config->toArray(),
            metadata: [
                'hydration' => $prompt->hydrationData,
            ]
        );
        $topics = $this->llmService->text($prompt, stream: false);
        $topics = json_validate($topics) ? json_decode($topics, associative: true) : false;
        $this->trace->closeGeneration(! empty($topics) ? $topics : 'Error extracting topics.');

        return $topics;
    }

    protected function generateHydrationData(PromptTemplate $prompt): array
    {
        $hydrationData = [];
        foreach ($prompt->hydratableAttributes as $attribute) {
            $hydrationData[$attribute] = $this->promptHydrationData[$prompt->name][$attribute]
                ?? match ($attribute) {
                    'town' => config('ask.town'),
                    'today_date' => now()->format('Y-m-d'),
                    'user_prompt', 'email_message', 'email_content' => $this->message,
                    'vectorial_database_provider' => 'Pinecone',
                    default => null
                };
        }

        return $hydrationData;
    }

    public function setLocale(Locale|string $locale): GenerateAnswerAction
    {
        if (is_string($locale)) {
            try {
                $locale = Locale::from($locale);
            } catch (Exception $e) {
                ExceptionsHandler::handle($e);
                $locale = Locale::ITALIAN;
            }
        }

        $this->locale = $locale;

        return $this;
    }

    protected function retrieveChunksFromVectorialDatabase(array $topics): array
    {
        if (empty($topics)) {
            return [];
        }

        $topics = UserMessageIntent::fromArray($topics)
            ->getQueriesCollection()
            ->push($this->message);

        $vectors = collect();

        $this->trace->beginSpan('generate_vector', input: $this->message);
        $embeds = $this->llmService->embed($topics->all());
        $this->trace->closeSpan($embeds);

        foreach ($topics as $index => $query) {
            $this->trace->beginSpan('search_vectors', input: [
                'query' => $query,
                'vector' => $embeds[$index],
            ]);
            $searchResults = $this->vectorialDatabaseService->search($embeds[$index], 5);
            $this->trace->closeSpan($searchResults);
            $vectors->push($searchResults);
        }

        return $vectors->flatten()->unique('id')->all();
    }

    protected function generateAnswer(array $contents): callable|string|false
    {
        /**
         * In some scenario we use this method multiple times for the same task (e.g., during email drafts generation),
         * so we need to clone the prompt to hydrate it with the correct user message.
         */
        $prompt = clone $this->answerGenerationPrompt;
        $prompt->hydrate($this->generateHydrationData($prompt));

        if ($this->streamOutput) {
            return $this->generateStream($contents, $prompt);
        }

        $this->beginAnswerTrace($prompt);

        $replyText = $this->llmService->text(
            prompt: $prompt,
            chat: $this->chat,
            retrievedContents: $contents,
            stream: false,
            locale: $this->locale,
            isLowDifficultyTask: $this->lowDifficultyTask
        );

        $this->finalizeAnswerTraceAndMessages($replyText);

        return $replyText;
    }

    protected function generateStream(array $contents, PromptTemplate $prompt): callable
    {
        return function () use ($contents, $prompt): void {
            $this->beginAnswerTrace($prompt);

            $replyText = '';

            $usage = [
                'promptToken' => null,
                'completionToken' => 0,
            ];

            try {
                foreach (
                    $this->llmService->text(
                        prompt: $prompt,
                        chat: $this->chat,
                        retrievedContents: $contents,
                        locale: $this->locale,
                    ) as $chunk
                ) {
                    echo $chunk->text;
                    ob_flush();
                    flush();
                    if ($chunk->usage !== null) {
                        if ($usage['promptToken'] === null) {
                            $usage['promptToken'] = $chunk->usage->promptTokens;
                        }
                        $usage['completionToken'] += $chunk->usage->completionTokens;
                    }
                    $replyText .= $chunk->text;
                }
            } finally {
                $this->finalizeAnswerTraceAndMessages($replyText);
            }
        };
    }

    protected function beginAnswerTrace(PromptTemplate $prompt): void
    {
        $this->trace->beginGeneration('answer_generation',
            prompt: $prompt,
            input: $this->message,
            model: config('ask.services.prism.chat.model'),
            modelParameters: $prompt->config->toArray(),
            metadata: [
                'hydration' => $prompt->hydrationData,
                ...($this->chat !== null ? ['preserve_context' => config('ask.preserve_history_during_chat')] : []),
            ]
        );
    }

    protected function finalizeAnswerTraceAndMessages(string $replyText): void
    {
        $this->trace->closeGeneration($replyText)->ingest();
        if ($this->chat !== null) {
            ChatService::addMessage($this->message, MessageType::USER, $this->chat);
            ChatService::addMessage($replyText, MessageType::ASSISTANT, $this->chat);
        }
    }

    /**
     * When Chat is provided, both the user prompt and the generated answer will be added to the chat.
     * The same chat will be used as context enrichment during the prompt crafting
     * if the relative config flag is set to true.
     */
    public function addMessagesToChat(int|Chat $chat): GenerateAnswerAction
    {
        $this->setChat($chat);

        return $this;
    }

    /**
     * If we need to preserve the context of the chat, we can use this method to set the chat. In that case,
     * the chat messages will be added to the LLM context during the prompt crafting.
     */
    protected function setChat(Chat|int|null $chat): void
    {
        if ($chat === null) {
            return;
        }

        if (is_int($chat)) {
            $this->chatId = $chat;
            $this->chat = Chat::find($chat);

            return;
        }

        if ($chat instanceof Chat) {
            $this->chat = $chat;
            $this->chatId = $chat->id;
        }
    }
}
