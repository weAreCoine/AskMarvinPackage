<?php

declare(strict_types=1);

namespace Marvin\Ask\Clients;

use Carbon\Carbon;
use Exception;
use Generator;
use InvalidArgumentException;
use Marvin\Ask\Abstracts\LlmProviderClient;
use Marvin\Ask\Entities\Prompt\PromptTemplate;
use Marvin\Ask\Enums\Locale;
use Marvin\Ask\Enums\MessageType;
use Marvin\Ask\Handlers\ExceptionsHandler;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Models\Message;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Enums\ToolChoice;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Embedding;
use Prism\Prism\ValueObjects\Media\Audio;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use function App\Utilities\compress;

class PrismClient extends LlmProviderClient
{
    protected \Prism\Prism\Tool $dateTimeTool;

    /**
     * Object schemas for the prompts. Array key is the prompt name.
     *
     * @var array<string, ObjectSchema>
     */
    protected array $schemas = [];

    public function __construct(public Prism $prismClient)
    {
        $this->chatProvider = Provider::from(config('services.prism.chat.provider'));
        $this->chatModel = config('services.prism.chat.model');

        $this->lowDifficultyChatProvider = Provider::from(config('services.prism.low_difficulty_tasks.provider'));
        $this->lowDifficultyChatModel = config('services.prism.low_difficulty_tasks.model');

        $this->embedProvider = Provider::from(config('services.prism.embed.provider'));
        $this->embedModel = config('services.prism.embed.model');

        $this->speechToTextProvider = Provider::from(config('services.prism.speech_to_text.provider', 'openai'));
        $this->textToSpeechProvider = Provider::from(config('services.prism.text_to_speech.provider', 'openai'));

        $this->speechToTextModel = config('services.prism.speech_to_text.model', 'gpt-4o-transcribe');

        $this->dateTimeTool = Tool::as('datetime')
            ->for('Get the current date and time in the format YYYY-MM-DD HH:MM:SS')
            ->using(fn() => Carbon::now()->format('Y-m-d H:i:s'));

        $this->schemas = [
            'topic_extractor' => new ObjectSchema(
                name: 'QueryPlannerResult',
                description: 'Output strutturato per il query planner RAG del Comune',
                properties: [
                    new StringSchema('locale', 'Codice ISO 639-1 della lingua usata dall\'utente'),
                    new StringSchema('topic', 'Macro-tema comunale'),
                    new ArraySchema('intents',
                        'I bisogni espressi dal cittadino nell\'email',
                        new StringSchema('intent', 'Singolo Intento')
                    ),
                    new ArraySchema(
                        'semantic_queries',
                        'Query semantiche per il query planner',
                        new StringSchema('query', 'Singola Query')
                    ),
                    new NumberSchema('confidence',
                        'Valore da 0 a 1 su quanto il modello è sicuro che le query coprano il bisogno'),
                ],
                requiredFields: ['locale', 'topic', 'intents', 'semantic_queries', 'confidence'],
                allowAdditionalProperties: false
            ),
        ];
    }

    public function embed(string $prompt): array
    {
        try {
            $response = Prism::embeddings()->using($this->embedProvider, $this->embedModel)
                ->fromInput($prompt)
                ->asEmbeddings();

            return $response->embeddings[0]->embedding;
        } catch (Exception $e) {
            ExceptionsHandler::handle($e, [
                'prompt' => $prompt,
                'provider' => $this->embedProvider,
                'model' => $this->embedModel,
            ]);

            return [];
        }
    }

    /**
     * @param array<string> $prompts
     * @return array<int, array<int, float>>
     */
    public function multipleEmbed(array $prompts): array
    {
        try {
            return collect(Prism::embeddings()->using($this->embedProvider, $this->embedModel)
                ->fromArray($prompts)
                ->asEmbeddings()->embeddings)->map(fn(Embedding $embedding) => $embedding->embedding)->toArray();
        } catch (Exception $e) {
            ExceptionsHandler::handle($e, [
                'prompts' => $prompts,
                'provider' => $this->embedProvider,
                'model' => $this->embedModel,
            ]);

            return [];
        }
    }

    public function speechToText(string $audioPath): string
    {
        try {
            $audioFile = Audio::fromLocalPath($audioPath);

            return $this->prismClient::audio()
                ->using($this->speechToTextProvider, $this->speechToTextModel)
                ->withInput($audioFile)
                ->withClientRetry(2)
                ->withProviderOptions([
                    'language' => app()->getLocale(),
                ])->asText()->text;
        } catch (InvalidArgumentException|Exception $e) {
            ExceptionsHandler::handle($e, [
                'audioPath' => $audioPath,
            ]);

            return '';
        }
    }

    public function text(
        PromptTemplate|string $prompt,
        ?Chat                 $chat = null,
        array                 $retrievedContents = [],
        bool                  $stream = true,
        bool                  $forceNotStructuredOutput = false,
        Locale                $locale = Locale::ITALIAN,
        bool                  $isLowDifficultyTask = false
    ): string|Generator
    {
        if ($prompt instanceof PromptTemplate && !empty($this->schemas[$prompt->name]) && !$forceNotStructuredOutput) {
            return $this->structured($prompt, $chat, $retrievedContents);
        }

        $provider = $this->chatProvider;
        $model = $this->chatModel;
        if ($isLowDifficultyTask) {
            $provider = $this->lowDifficultyChatProvider;
            $model = $this->lowDifficultyChatModel;
        }

        try {
            $pendingRequest = $this->prismClient::text()
                ->using($provider, $model)
                ->withTools([$this->dateTimeTool])
                ->withToolChoice(ToolChoice::Auto)
                ->withMaxSteps(2)
                ->usingTemperature($prompt->config->settings->temperature ?? 0.1)
                ->usingTopP($prompt->config->settings->top_p ?? 0.7)
                ->withClientRetry($prompt->config->settings->max_retries ?? 6)
                ->withMessages(
                    $this->generateConversation($prompt, $chat, $retrievedContents, $locale)
                );

            return $stream ? $pendingRequest->asStream() : $pendingRequest->asText()->text;
        } catch (Exception $e) {
            ExceptionsHandler::handle($e);

            return __('Si è verificato un errore durante la generazione della risposta.');
        }
    }

    protected function structured(
        PromptTemplate $prompt,
        ?Chat          $chat = null,
        array          $retrievedContents = [],
        Locale         $locale = Locale::ITALIAN,
        bool           $isLowDifficultyTask = false
    ): string
    {
        $provider = $this->chatProvider;
        $model = $this->chatModel;
        if ($isLowDifficultyTask) {
            $provider = $this->lowDifficultyChatProvider;
            $model = $this->lowDifficultyChatModel;
        }

        $pendingRequest = $this->prismClient::structured()
            ->using($provider, $model)
            ->withSchema($this->schemas[$prompt->name])
            ->usingTemperature($prompt->config->settings->temperature ?? 0.1)
            ->usingTopP($prompt->config->settings->top_p ?? 0.7)
            ->withClientRetry($prompt->config->settings->max_retries ?? 6)
            ->withMessages(
                $this->generateConversation($prompt, $chat, $retrievedContents, $locale)
            )
            ->asStructured();

        return $pendingRequest->text;
    }

    /**
     * @param array $retrievedContents Array will be sliced to 5 elements.
     */
    public function generateConversation(
        PromptTemplate|string $prompt,
        ?Chat                 $chat = null,
        array                 $retrievedContents = [],
        Locale                $locale = Locale::ITALIAN
    ): array
    {
        $retrievedContents = array_slice($retrievedContents, 0, 20);
        $conversation = [];
        $isStringPrompt = is_string($prompt);
        $systemPrompt = $isStringPrompt ? null : $prompt->getSystemPrompt();
        if (!empty($systemPrompt)) {
            $conversation[] = new SystemMessage(compress($systemPrompt));
        }
        $conversation[] = new SystemMessage(compress(
            'Ti viene fornito un array di documenti recuperati (JSON). ' .
            'Utilizza SOLO questi documenti per affermazioni basate su fatti. ' .
            "Preferisci lo `score` più alto e l'`updated_at` più recente per ogni documento." .
            'JSON:' . json_encode($retrievedContents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ));
        $conversation[] = new SystemMessage(sprintf('La risposta deve essere in lingua con codice ISO639-1: %s',
            $locale->value));

        if (config('marvin.preserve_history_during_chat')) {
            $conversation = array_merge($conversation, $this->conversationFromChat($chat));
        }

        $conversation[] = new UserMessage(compress(is_string($prompt) ? $prompt : $prompt->getUserPrompt()));

        return $conversation;
    }

//    /**
//     * @param array $retrievedContents
//     *
//     * TODO Retrieved Content may be split by topic, so we can slice on every topic
//     */
//    public function generateConversation(
//        PromptTemplate|string $prompt,
//        ?Chat                 $chat = null,
//        array                 $retrievedContents = [],
//        Locale                $locale = Locale::ITALIAN
//    ): array
//    {
//        $usage = 0;
//        $usageLimit = config('services.prism.chat_model_limits.tpm');
//        $conversation = [];
//        /**
//         * Passaggi per il reformat.
//         *  1. Preparo l'ultimo messaggio dell'utente e conto i token
//         *  2. Preparo i chunk
//         */
//
//        //Adding the last user message, the only required field.
//        $userMessage = compress(is_string($prompt) ? $prompt : $prompt->getUserPrompt());
//        $usage += countTokens($userMessage);
//
//        if ($usage > $usageLimit) {
//            throw new InvalidArgumentException('Usage limit exceeded');
//        }
//
//        $conversation[] = new UserMessage($userMessage);
//
//        //We add the chat
//        if (config('marvin.preserve_history_during_chat')) {
//            $conversation = array_merge($conversation, $this->conversationFromChat($chat));
//        }
//
//        // Adding the retrieved contents. It's mandatory to add at least the first one.
//        $systemMessage = 'Ti viene fornito un array di documenti recuperati (JSON). Utilizza SOLO questi documenti per affermazioni basate su fatti. Preferisci lo `score` più alto e l\'`updated_at` più recente per ogni documento. JSON:';
//        $systemMessageTokens = countTokens($systemMessage) + 2; //Add two for the JSON brackets
//
//        if ($usage + $systemMessageTokens > $usageLimit) {
//            throw new InvalidArgumentException('Usage limit exceeded');
//        }
//
//        $usage += $systemMessageTokens;
//
//        $chunks = [];
//        foreach (array_slice($retrievedContents, 20) as $chunk) {
//            $json = json_encode($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//            $tokens = countTokens($json);
//            if ($usage + $tokens > $usageLimit) {
//                break;
//            }
//            $chunks[] = $json;
//        }
//        if (empty($chunks)) {
//            throw new InvalidArgumentException('Usage limit exceeded');
//        }
//
//        $jsonChunks = '[' . implode(',', $chunks) . ']';
//
//        $conversation[] = new SystemMessage($systemMessage . $jsonChunks);
//
//        //Adding the locale message
//        $localeMessage = sprintf('La risposta deve essere in lingua con codice ISO639-1: %s',
//            $locale->value);
//        $tokens = countTokens($localeMessage);
//        if ($usage + $tokens > $usageLimit) {
//            return array_reverse($conversation);
//        }
//
//        $conversation[] = new SystemMessage($localeMessage);
//
//        return array_reverse($conversation);
//    }


    public function conversationFromChat(?Chat $chat): array
    {
        if ($chat === null) {
            return [];
        }

        return $chat->messages->map(
            fn(Message $message) => $message->type === MessageType::USER ?
                new UserMessage(compress($message->content)) :
                new AssistantMessage(compress($message->content))
        )->toArray();

    }

    protected function prepareForAudio()
    {
        // TODO Implement method
    }
}
