<?php

declare(strict_types=1);

namespace Marvin\Ask\DataTransferObjects\Langfuse;

use Carbon\Carbon;
use Exception;
use Marvin\Ask\Abstracts\AbstractDataTransferObject;
use Marvin\Ask\Contracts\DtoContract;
use Marvin\Ask\Entities\Prompt\PromptTemplate;

final class LangfusePrompt extends AbstractDataTransferObject implements DtoContract
{
    public function __construct(
        public ?string $id,
        public Carbon $createdAt,
        public Carbon $updatedAt,
        public string $projectId,
        public string $createdBy,
        public string $name,
        public int $version,
        public string $type,
        public ?bool $isActive,
        public ?string $commitMessage,
        /** @var LangfusePromptMessage[] */
        public array $prompt,
        public LangfusePromptConfig $config,
        public array $tags,
        public array $labels,
        public mixed $resolutionGraph,
    ) {}

    /**
     * @throws Exception
     */
    protected static function mapDataBeforeCreatingNewInstance(array $data): array
    {
        if (empty($data)) {
            throw new Exception('Unable to retrieve data from Langfuse API.');
        }
        $data['isActive'] = ($data['isActive'] ?? false) === 'true';
        $data['createdAt'] = ! empty($data['createdAt']) ? Carbon::parse($data['createdAt']) : null;
        $data['updatedAt'] = ! empty($data['updatedAt']) ? Carbon::parse($data['updatedAt']) : null;

        if (is_array($data['prompt'])) {
            $data['prompt'] = array_map(fn ($item) => LangfusePromptMessage::fromArray($item), $data['prompt']);
        } elseif (empty($data['prompt'])) {
            $data['prompt'] = [];
        } else {
            $data['prompt'] = [
                'role' => 'user',
                'content' => $data['prompt'],
            ];
        }

        $data['config'] = LangfusePromptConfig::fromArray($data['config'] ?? []);

        return $data;
    }

    public function toEntity(): PromptTemplate
    {
        return new PromptTemplate(
            id: $this->id,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            name: $this->name,
            version: $this->version,
            prompt: array_map(fn (LangfusePromptMessage $message) => $message->toEntity(), $this->prompt),
            config: $this->config->toEntity(),
            hydratableAttributes: $this->extractHydratableAttributes()
        );
    }

    protected function extractHydratableAttributes(): array
    {
        $pattern = '/\{\{\s*([^{}]+?)\s*}}/u';

        $allPlaceholders = [];

        foreach ($this->prompt as $message) {
            $currentMatches = [];
            preg_match_all($pattern, $message->content, $currentMatches);
            if (! empty($currentMatches[1])) {
                $allPlaceholders = array_merge($allPlaceholders, $currentMatches[1]);
            }
        }

        return array_values(array_unique($allPlaceholders));
    }
}
