<?php

declare(strict_types=1);

namespace Marvin\Ask\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Marvin\Ask\Handlers\ExceptionsHandler;

readonly class WhatsAppClient
{
    final public string $verifyToken;

    final protected string $graphVersion;

    final protected string $phoneNumberId;

    public function __construct()
    {
        $this->verifyToken = config('services.whatsapp.verification_token');
        $this->graphVersion = config('services.whatsapp.graph_version');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    /**
     * @param  bool  $putTypingIndicator  The typing indicator will be dismissed once you respond, or after 25 seconds, whichever comes first.
     */
    public function markAsRead(string $messageId, ?string $phoneNumberId = null, bool $putTypingIndicator = true): bool
    {
        Log::info('WhatsApp markAsRead', ['message_id' => $messageId, 'phone_number_id' => $phoneNumberId]);

        $phoneNumberId ??= $this->phoneNumberId;

        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        if ($putTypingIndicator) {
            $payload['typing_indicator'] = ['type' => 'text'];
        }

        try {
            $response = Http::withToken($this->verifyToken)
                ->post($this->generateUrl('messages'), $payload);
            if ($response->failed()) {
                Log::error('WhatsApp markAsRead failed', [
                    'message_id' => $messageId,
                    'phone_number_id' => $phoneNumberId,
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException $e) {
            ExceptionsHandler::handle($e);

            return false;
        }
    }

    protected function generateUrl(string $endpoint): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/%s',
            $this->graphVersion, $this->phoneNumberId, $endpoint,
        );
    }

    public function sendText(string $to, string $body): bool
    {
        try {
            Http::withToken($this->verifyToken)
                ->post($this->generateUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => mb_substr($body, 0, 4096),
                    ],
                ])->throw();

            return true;
        } catch (ConnectionException|RequestException $e) {
            ExceptionsHandler::handle($e);

            return false;
        }
    }
}
