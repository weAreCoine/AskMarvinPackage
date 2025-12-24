<?php

declare(strict_types=1);

namespace Marvin\Ask\Factories;

use Google\Client as GoogleClient;
use Google\Exception;
use Google\Service\Gmail;
use Marvin\Ask\Abstracts\AbstractEmailClientFactory;
use Marvin\Ask\Clients\EmailClients\Gmail\GmailClient;
use Marvin\Ask\Handlers\ExceptionsHandler;

final class GmailClientFactory extends AbstractEmailClientFactory
{
    public function __construct() {}

    public function for(string $userEmail, array $scopes = []): ?GmailClient
    {
        $client = new GoogleClient;
        try {
            $client->setAuthConfig(config('ask.services.google_api.sa_key_path'));
            $client->setApplicationName(config('app.name', 'Marvin Gmail'));
            $client->setSubject($userEmail);
            $client->setScopes(
                $scopes ?:
                    config(
                        'ask.services.google_api.scopes',
                        [
                            Gmail::GMAIL_COMPOSE, Gmail::GMAIL_SEND, Gmail::GMAIL_READONLY, Gmail::GMAIL_MODIFY,
                        ]
                    )
            );
            $client->fetchAccessTokenWithAssertion();

            return new GmailClient(new Gmail($client));
        } catch (Exception $e) {
            ExceptionsHandler::handle($e);

            return null;
        }
    }
}
