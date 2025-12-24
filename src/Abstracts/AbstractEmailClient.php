<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Marvin\Ask\Contracts\EmailClientContract;
use Marvin\Ask\Entities\Email\EmailMessage;

abstract class AbstractEmailClient implements EmailClientContract
{
    abstract public static function for(string $userEmail, array $scopes = []): ?self;


    abstract public function getMessages(
        int     $limit = 10,
        bool    $includeSpam = false,
        string  $filterFrom = '',
        string  $filterSubject = '',
        string  $searchQuery = '',
        bool    $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection;


    abstract public function getInboxMessages(
        int     $limit = 10,
        bool    $includeSpam = false,
        string  $filterFrom = '',
        string  $filterSubject = '',
        string  $searchQuery = '',
        bool    $unreadOnly = false,
        ?Carbon $since = null,
    ): Collection;


    abstract public function last(): ?EmailMessage;

    abstract public function saveDraft(EmailMessage $original, EmailMessage $draftMessage, bool $replyAll = false);

    abstract public function reply(
        EmailMessage $emailMessage,
        string       $replyText,
        bool         $send = false,
    );
}
