<?php

declare(strict_types=1);

namespace Marvin\Ask\Clients\EmailClients\Gmail;

use Google\Service\Exception;
use Google\Service\Gmail;
use Google\Service\Gmail\Draft;
use Google\Service\Gmail\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Marvin\Ask\Abstracts\AbstractEmailClient;
use Marvin\Ask\Entities\Email\EmailMessage;
use Marvin\Ask\Factories\GmailClientFactory;
use Marvin\Ask\Handlers\ExceptionsHandler;
use function Helpers\markdownConverter;

final class GmailClient extends AbstractEmailClient
{


    public function __construct(protected Gmail $client)
    {
    }

    public static function for(string $userEmail, array $scopes = []): ?GmailClient
    {
        return new GmailClientFactory()->for($userEmail, $scopes);
    }

    public function last(): ?EmailMessage
    {
        return $this->getMessages(1)?->first() ?? null;
    }

    /**
     * @param int $limit
     * @param bool $includeSpam
     * @param string $filterFrom
     * @param string $filterSubject
     * @param string $searchQuery
     * @param bool $unreadOnly
     * @param Carbon|null $since
     * @return Collection<int, EmailMessage>
     */
    public function getMessages(
        int     $limit = 10,
        bool    $includeSpam = false,
        string  $filterFrom = '',
        string  $filterSubject = '',
        string  $searchQuery = '',
        bool    $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection
    {
        $params = [
            'maxResults' => $limit,
            'includeSpamTrash' => $includeSpam,
        ];

        if (!empty($searchQuery)) {
            $query[] = $searchQuery;
        }

        if (!empty($filterFrom)) {
            $query[] = 'from:' . $filterFrom;
        }
        if (!empty($since)) {
            $query[] = 'after:' . $since->utc()->timestamp;
        }

        if (!empty($filterSubject)) {
            $query[] = 'subject:' . $filterSubject;
        }

        if ($unreadOnly) {
            $query[] = 'is:unread';
        }

        if (!empty($searchQuery)) {
            $params['q'] = implode(' ', $query);;
        }


        try {
            /**
             * For the optParams array keys,
             * @see https://developers.google.com/gmail/api/v1/reference/users/messages/list
             */
            $list = $this->client->users_messages->listUsersMessages('me', $params);
            return collect($list->getMessages() ?? [])->map(
                function (Gmail\Message $message) {
                    $full = $this->client->users_messages->get('me', $message->getId(), ['format' => 'full']);
                    return GmailMessageParser::extract($full);
                }
            );
        } catch (Exception $e) {
            ExceptionsHandler::handle($e);
            return collect();
        }
    }

    /**
     * @param int $limit
     * @param bool $includeSpam
     * @param string $filterFrom
     * @param string $filterSubject
     * @param string $searchQuery
     * @param bool $unreadOnly
     * @return Collection<int, EmailMessage>
     */
    public function getInboxMessages(
        int     $limit = 10,
        bool    $includeSpam = false,
        string  $filterFrom = '',
        string  $filterSubject = '',
        string  $searchQuery = '',
        bool    $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection
    {
        if (empty($searchQuery)) {
            $searchQuery = 'in:inbox';
        } else {
            $searchQuery = 'in:inbox ' . $searchQuery;
        }

        return $this->getMessages($limit, $includeSpam, $filterFrom, $filterSubject, $searchQuery, $unreadOnly, $since);
    }

    /**
     * @param EmailMessage $emailMessage
     * @param string $replyText
     * @param bool $send If true, the email will be sent immediately. If false, it will be saved as a draft.
     * @return string
     */
    public function reply(
        EmailMessage $emailMessage,
        string       $replyText,
        bool         $send = false,
    ): string
    {
        $reply = clone $emailMessage;
        $reply->to = $emailMessage->from;
        $reply->from = $emailMessage->to;
        if (!str_starts_with($reply->subject, 're: ')) {
            $reply->subject = 're: ' . $reply->subject;
        }
        $reply->html = markdownConverter()->mdToHtml($replyText);
        $reply->text = $replyText;

        return $this->saveDraft($emailMessage, $reply, true);
    }

    public function saveDraft(EmailMessage $original, EmailMessage $draftMessage, bool $replyAll = false): string
    {
        $headers = collect($original->originalMessage->getPayload()->getHeaders())
            ->mapWithKeys(fn($h) => [strtolower($h->getName()) => $h->getValue()]);
        $subjectOld = $headers->get('subject') ?? '';
        $messageIdHeader = self::normalizeAngle($headers->get('message-id') ?? null);
        $referencesOld = trim($headers->get('references') ?? '');
        $replyTo = $headers->get('reply-to') ?: $headers->get('from');
        $toList = self::parseAddressList($replyTo);
        $ccList = [];
        if ($replyAll) {
            $toList = array_merge($toList, self::parseAddressList($headers->get('to')));
            $ccList = array_merge($ccList, self::parseAddressList($headers->get('cc')));

            $all = self::uniqueEmails(array_merge($toList, $ccList));
            $all = array_values(array_filter($all,
                fn($addr) => strcasecmp(self::emailOnly($addr), 'chatbot@askmarvin.it') !== 0));
            // ricostruisci to/cc: mantieni più to possibili, cc il resto
            $toList = array_slice($all, 0, 10);
            $ccList = array_slice($all, 10);
        }

        $toHeader = self::joinAddresses($toList);
        $ccHeader = self::joinAddresses($ccList);

        // 3) Subject
        $subject = $overrideSubject ?? (preg_match('/^re:/i', $subjectOld) ? $subjectOld : 'Re: ' . $subjectOld);

        // 4) Intestazioni di threading
        $references = trim($referencesOld . ' ' . ($messageIdHeader ?? ''));
        $references = preg_replace('/\s+/', ' ', $references); // normalizza spazi

        // 5) MIME multipart/alternative (text + html)
        $textBody = self::toPlainText($draftMessage->html);
        $boundary = 'bndry_' . bin2hex(random_bytes(8));

        $fromHeader = $draftMessage->from ? sprintf('%s <%s>', self::encodeHeaderName($draftMessage->from),
            'chatbot@askmarvin.it') : 'chatbot@askmarvin.it';

        $raw =
            "From: {$fromHeader}\r\n" .
            "To: {$toHeader}\r\n" .
            ($ccHeader ? "Cc: {$ccHeader}\r\n" : '') .
            "Subject: " . self::encodeHeader($subject) . "\r\n" .
            ($messageIdHeader ? "In-Reply-To: {$messageIdHeader}\r\n" : '') .
            ($references ? "References: {$references}\r\n" : '') .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n" .
            "\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 7bit\r\n\r\n" .
            $textBody . "\r\n\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 7bit\r\n\r\n" .
            self::wrapHtml($draftMessage->html) . "\r\n\r\n" .
            "--{$boundary}--\r\n";

        $rawB64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $msg = new Message([
            'raw' => $rawB64,
            'threadId' => $original->threadId,
        ]);

        $draft = new Draft(['message' => $msg]);
        $res = $this->client->users_drafts->create('me', $draft);

        return (string)$res->getId();
    }

    private static function normalizeAngle(?string $val): ?string
    {
        if (!$val) {
            return null;
        }
        $v = trim($val);
        if (!str_starts_with($v, '<')) {
            $v = '<' . $v;
        }
        if (!str_ends_with($v, '>')) {
            $v .= '>';
        }
        return $v;
    }

    private static function parseAddressList(?string $raw): array
    {
        if (!$raw) {
            return [];
        }
        // split semplice; per casi complessi valuta un parser RFC più robusto
        $parts = preg_split('/,(?=(?:[^"]*"[^"]*")*[^"]*$)/', $raw);
        return array_values(array_filter(array_map('trim', $parts)));
    }

    private static function uniqueEmails(array $addresses): array
    {
        $seen = [];
        $out = [];
        foreach ($addresses as $a) {
            $key = strtolower(self::emailOnly($a));
            if (!$key || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $a;
        }
        return $out;
    }

    private static function emailOnly(string $addr): string
    {
        if (preg_match('/<([^>]+)>/', $addr, $m)) {
            return trim($m[1]);
        }
        return trim($addr);
    }

    private static function joinAddresses(array $list): string
    {
        return implode(', ', array_filter($list));
    }

    private static function toPlainText(string $html): string
    {
        // fallback semplice: strip tag + normalizza spazi
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        // avvolgi a 78 col (mail friendly)
        return wordwrap(trim($text), 78);
    }

    private static function encodeHeaderName(string $name): string
    {
        // preserva eventuali virgolette
        $enc = mb_encode_mimeheader($name, 'UTF-8', 'B', "\r\n");
        // rimuovi doppi apici se creati da mb_encode_mimeheader per compat
        return trim($enc, '"');
    }

    private static function encodeHeader(string $value): string
    {
        // RFC 2047 (UTF-8)
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    private static function wrapHtml(string $html): string
    {
        // se già include <html> assumiamo completo
        if (stripos($html, '<html') !== false) {
            return $html;
        }
        return "<!doctype html><html><body>{$html}</body></html>";
    }
}
