<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\Prompt;

final readonly class PromptConfig
{
    public function __construct(
        public string $description,
        public int    $seed,
        public float  $top_p,
        public float  $temperature,
        public float  $repeat_penalty,
        public int    $max_retries
    )
    {
    }

    public function toArray(): array
    {
        return [
            'seed' => $this->seed,
            'top_p' => $this->top_p,
            'temperature' => $this->temperature,
            'repeat_penalty' => $this->repeat_penalty,
            'max_retries' => $this->max_retries,
        ];
    }
}
