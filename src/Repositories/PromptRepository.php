<?php

declare(strict_types=1);

namespace Marvin\Ask\Repositories;

use Marvin\Ask\Abstracts\AbstractTracingClient;
use Marvin\Ask\Clients\LangfuseClient;
use Marvin\Ask\Entities\Prompt\PromptTemplate;

/**
 * @property LangfuseClient $promptProvider
 */
class PromptRepository
{
    public function __construct(protected AbstractTracingClient $promptProvider) {}

    public function get(
        string $promptName,
        ?string $label = null,
        ?int $version = null
    ): PromptTemplate|false {
        $promptDto = $this->promptProvider->getPrompt($promptName, $label, $version);

        if ($promptDto === false) {
            return false;
        }

        return $promptDto->toEntity();
    }
}
