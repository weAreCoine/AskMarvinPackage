<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Marvin\Ask\Entities\Email\EmailMessage;

interface EmailClientContract
{
    public static function for(string $userEmail, array $scopes = []): ?self;

    public function getMessages(
        int $limit = 10,
        bool $includeSpam = false,
        string $filterFrom = '',
        string $filterSubject = '',
        string $searchQuery = '',
        bool $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection;

    public function getInboxMessages(
        int $limit = 10,
        bool $includeSpam = false,
        string $filterFrom = '',
        string $filterSubject = '',
        string $searchQuery = '',
        bool $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection;

    public function last(): ?EmailMessage;

    public function reply(
        EmailMessage $emailMessage,
        string $replyText,
        bool $send = false,
    );

    public function saveDraft(EmailMessage $original, EmailMessage $draftMessage, bool $replyAll = false);
}
