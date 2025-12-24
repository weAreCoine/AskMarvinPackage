<?php

declare(strict_types=1);

namespace Marvin\Ask\Clients\EmailClients\Gmail;

use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Illuminate\Support\Carbon;
use Marvin\Ask\Entities\Email\EmailMessage;

class GmailMessageParser
{
    /**
     * Estrae headers utili + corpo (HTML preferito, altrimenti plain).
     * @return EmailMessage
     */
    public static function extract(Message $message): EmailMessage
    {
        $payload = $message->getPayload();
        $headers = collect($payload->getHeaders())
            ->mapWithKeys(fn($h) => [strtolower($h->getName()) => $h->getValue()]);

        [$html, $text] = self::extractBody($payload->getParts(), $payload->getBody()?->getData());


        return new EmailMessage(
            id: $message->getId(),
            threadId: $message->getThreadId(),
            from: $headers->get('from', ''),
            to: $headers->get('to', ''),
            subject: $headers->get('subject', ''),
            date: Carbon::parse($headers->get('date', '')),
            snippet: $message->getSnippet(),
            html: $html,
            text: $text,
            originalMessage: $message,
        );
    }

    private static function extractBody(?array $parts, ?string $rootData): array
    {
        $html = null;
        $text = null;

        // se non ci sono parts, prova il body root
        if ((!$parts || count($parts) === 0) && $rootData) {
            $decoded = self::decodeBody($rootData);
            // non conosciamo il mime: metti tutto in text
            $text = $decoded;
            return [$html, $text];
        }

        $stack = $parts ?? [];
        while ($stack) {
            /** @var MessagePart $p */
            $p = array_shift($stack);
            $mime = strtolower($p->getMimeType() ?? '');

            if (str_starts_with($mime, 'multipart/')) {
                foreach ($p->getParts() ?? [] as $child) {
                    $stack[] = $child;
                }
                continue;
            }

            $data = $p->getBody()?->getData();
            if (!$data) {
                continue;
            }

            $decoded = self::decodeBody($data);

            if ($mime === 'text/html' && $html === null) {
                $html = $decoded;
            } elseif ($mime === 'text/plain' && $text === null) {
                $text = $decoded;
            }
        }

        if ($html === null && $text !== null) {
            $html = nl2br(e($text));
        }

        return [$html, $text];
    }

    private static function decodeBody(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
