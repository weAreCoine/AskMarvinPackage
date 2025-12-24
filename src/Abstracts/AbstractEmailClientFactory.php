<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Marvin\Ask\Clients\EmailClients\Gmail\GmailClient;

abstract class AbstractEmailClientFactory
{
    abstract public function for(string $userEmail, array $scopes = []): ?GmailClient;

}
