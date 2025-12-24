<?php

declare(strict_types=1);

namespace Marvin\Ask\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Marvin\Ask\Abstracts\AbstractEmailClient;
use Marvin\Ask\Abstracts\AbstractEmailClientFactory;
use Marvin\Ask\Clients\EmailClients\Gmail\GmailClient;
use Marvin\Ask\Entities\Email\EmailMessage;

class EmailService
{
    /**
     * @property GmailClient $emailClient
     */
    protected AbstractEmailClient $emailClient;

    public function __construct(protected string $userEmail, protected array $scopes = [])
    {
        $this->emailClient = app(AbstractEmailClientFactory::class)
            ->for(
                $this->userEmail,
                $this->scopes
            );
    }

    /**
     * @return Collection<int, EmailMessage>
     */
    public function getInboxMessages(
        int $limit = 10,
        bool $includeSpam = false,
        string $filterFrom = '',
        string $filterSubject = '',
        bool $unreadOnly = false,
        ?Carbon $since = null,

    ): Collection {
        return $this->emailClient->getInboxMessages(
            $limit,
            $includeSpam,
            $filterFrom,
            $filterSubject,
            unreadOnly: $unreadOnly,
            since: $since,

        );
    }

    /**
     * @return Collection<int, EmailMessage>
     */
    public function getMessages(
        int $limit = 10,
        bool $includeSpam = false,
        string $filterFrom = '',
        string $filterSubject = '',
        bool $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection {
        return $this->emailClient->getMessages(
            $limit,
            $includeSpam,
            $filterFrom,
            $filterSubject,
            unreadOnly: $unreadOnly,
            since: $since,
        );
    }

    public function last(): ?EmailMessage
    {
        return $this->emailClient->last();
    }

    public function reply(
        EmailMessage $emailMessage,
        string $replyText,
        bool $send = false,

    ) {
        return $this->emailClient->reply($emailMessage, $replyText, $send);
    }
}
