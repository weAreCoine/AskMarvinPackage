<?php

declare(strict_types=1);

namespace Marvin\Ask\DataTransferObjects\Langfuse;

use Marvin\Ask\Abstracts\AbstractDataTransferObject;

final class LangfusePromptSettings extends AbstractDataTransferObject
{
    public function __construct(
        public int $seed,
        public float $top_p,
        public float $temperature,
        public float $repeat_penalty,
        public int $max_retries
    ) {}

    public function toArray(): array
    {
        return (array) $this;
    }
}
