<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

interface PromptTemplateContract
{
    public function hydrate(array $attributes): static;

    public function getUserPrompt(): string;

    public function getSystemPrompt(): string;
}
