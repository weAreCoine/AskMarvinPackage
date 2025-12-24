<?php

namespace Marvin\Ask\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Marvin\Ask\Actions\GenerateAnswerAction;
use Marvin\Ask\Clients\WhatsAppClient;
use Marvin\Ask\Handlers\ExceptionsHandler;
use Marvin\Ask\Models\WhatsAppMessages;

class AnswerToWhatsAppMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected bool $sendWaitingMessage;

    protected string $waitingMessage;

    protected bool $canRun = false;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $payload)
    {
        $this->sendWaitingMessage = config('ask.services.whatsapp.send_waiting_message', false);
        $this->waitingMessage = __('Ho ricevuto il tuo messaggio... attendi qualche secondo.');
        $this->canRun = ! empty($payload['messages']) && is_array($payload['messages']);
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppClient $whatsAppClient): void
    {
        if (! $this->canRun) {
            return;
        }

        Log::info('Received WAMID: '.($this->payload['messages'][0]['id'] ?? 'UNKWNOWN'), $this->payload);
        Log::info('WHATSAPP ENV SNAPSHOT', [
            'app_env' => config('app.env'),
            'town' => config('ask.town'),
            'pinecone_env' => config('ask.services.pinecone.environment'),
            'pinecone_index' => config('ask.services.pinecone.index'),
            'pinecone_namespace' => config('ask.services.pinecone.namespace'),
            'model' => config('ask.services.prism.chat.model'),
        ]);

        $whatsappMessageData = [
            'sender_name' => $this->payload['contacts'][0]['profile']['name'] ?? null,
            'sender_whatsapp_id' => $this->payload['contacts'][0]['wa_id'] ?? null,
            'display_phone_number' => $this->payload['metadata']['display_phone_number'] ?? null,
            'phone_number_id' => $this->payload['metadata']['phone_number_id'] ?? null,
        ];

        foreach ($this->payload['messages'] ?? [] as $message) {
            $whatsAppMessageId = $message['id'] ?? null;

            if ($message['type'] !== 'text' || empty($whatsAppMessageId)) {
                continue;
            }

            if (WhatsAppMessages::where('message_id', $whatsAppMessageId)->exists()) {
                Log::warning("Skipping duplicate message ID: $whatsAppMessageId. Already processed.");

                continue;
            }

            $userMessage = $message['text']['body'] ?? '';
            if (empty($userMessage)) {
                continue;
            }

            try {
                $whatsAppClient->markAsRead(
                    $whatsAppMessageId,
                    $whatsappMessageData['phone_number_id']
                );

                if (config('ask.services.whatsapp.send_waiting_message')) {
                    $whatsAppClient->sendText($whatsappMessageData['sender_whatsapp_id'],
                        'Ho ricevuto il tuo messaggio... attendi qualche secondo.');
                }

                $answer = GenerateAnswerAction::make()->init(
                    answerGenerationPromptName: 'whatsapp_answer_generation',
                    traceName: 'marvin_whatsapp_',
                )->run($userMessage);

                $whatsAppClient->sendText($whatsappMessageData['sender_whatsapp_id'], $answer);

                $whatsAppModelData = array_merge($whatsappMessageData, [
                    'content' => $message['text']['body'] ?? null,
                    'timestamp' => $message['timestamp'] ?? null,
                    'message_id' => $whatsAppMessageId,
                    'reply_content' => $answer,
                ]);

                WhatsAppMessages::create($whatsAppModelData);

                Log::info("Successfully processed WAMID: $whatsAppMessageId and sent reply.", [
                    'sender' => $whatsappMessageData['sender_whatsapp_id'],
                    'inbound_content' => $userMessage,
                    'reply_content' => $answer,
                ]);
            } catch (\Exception $e) {
                ExceptionsHandler::handle($e, ['payload' => $this->payload, 'message' => $userMessage]);
            }
        }
    }
}
