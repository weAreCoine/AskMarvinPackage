<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\Prompt;

use Carbon\Carbon;
use Marvin\Ask\Contracts\PromptTemplateContract;
use Marvin\Ask\DataTransferObjects\Langfuse\LangfusePrompt;

/**
 * @property LangfusePrompt $prompt
 * @property PromptMessage[] $messages
 */
class PromptTemplate implements PromptTemplateContract
{
    public bool $needHydration {
        get => ! empty($this->hydratableAttributes);
    }

    public bool $hydrated = false;

    public function __construct(
        public ?string $id,
        public Carbon $createdAt,
        public Carbon $updatedAt,
        public string $name,
        public int $version,
        public array $prompt,
        public mixed $config,
        public array $hydratableAttributes,
        public array $hydrationData = []
    ) {
        $this->hydrated = empty($this->hydratableAttributes);
    }

    public function cloneAndHydrate(array $attributes): static
    {
        $clone = clone $this;

        return $clone->hydrate($attributes);
    }

    public function hydrate(array $attributes = []): static
    {
        foreach ($this->prompt as $message) {
            $message->content = str_replace(
                array_map(fn (string $key) => "{{{$key}}}", array_keys($attributes)),
                array_values($attributes), $message->content);
        }
        $this->hydrationData = $attributes;
        $this->hydrated = true;

        return $this;
    }

    public function getUserPrompt(): string
    {
        foreach ($this->prompt as $message) {
            if ($message->role === 'user') {
                return $message->content;
            }
        }

        return '';
    }

    public function getSystemPrompt(): string
    {
        foreach ($this->prompt as $message) {
            if ($message->role === 'system') {
                return $message->content;
            }
        }

        return '';
    }
}
