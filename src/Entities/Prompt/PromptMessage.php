<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\Prompt;

final class PromptMessage
{
    public function __construct(
        public ?string $role = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $content = null,
    ) {}
}
