<?php

declare(strict_types=1);

namespace Marvin\Ask\DataTransferObjects\Langfuse;

use Marvin\Ask\Abstracts\AbstractDataTransferObject;
use Marvin\Ask\Contracts\DtoContract;
use Marvin\Ask\Entities\Prompt\PromptConfig;

/**
 * All the content in this response node is generated from configuration data,
 * which is user-generated and doesn't need to follow a fixed syntax to be accepted by Langfuse as valid config.
 * In Marvin, WE ASSUME that the prompt configuration includes a description and a set of settings.
 */
final class LangfusePromptConfig extends AbstractDataTransferObject implements DtoContract
{
    public function __construct(
        public ?string $description,
        public ?LangfusePromptSettings $settings
    ) {}

    protected static function mapDataBeforeCreatingNewInstance(array $data): array
    {
        $data['description'] ??= null;
        $data['settings'] = ! empty($data['settings']) ? LangfusePromptSettings::fromArray($data['settings']) : null;

        return $data;
    }

    public function toEntity(): PromptConfig
    {
        return new PromptConfig(
            $this->description,
            $this->settings->seed,
            $this->settings->top_p,
            $this->settings->temperature,
            $this->settings->repeat_penalty,
            $this->settings->max_retries,
        );
    }
}
