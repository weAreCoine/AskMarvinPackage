<?php

declare(strict_types=1);

namespace Marvin\Ask\DataTransferObjects\Langfuse;

use Marvin\Ask\Abstracts\AbstractDataTransferObject;
use Marvin\Ask\Contracts\DtoContract;
use Marvin\Ask\Entities\Prompt\PromptMessage;

final class LangfusePromptMessage extends AbstractDataTransferObject implements DtoContract
{
    public function __construct(
        public ?string $role = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $content = null,
    ) {}

    public function toEntity(): PromptMessage
    {
        return new PromptMessage(
            role: $this->role,
            name: $this->name,
            type: $this->type,
            content: $this->content,
        );
    }
}
