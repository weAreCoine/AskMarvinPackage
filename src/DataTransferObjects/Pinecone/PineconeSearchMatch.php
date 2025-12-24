<?php

declare(strict_types=1);

namespace Marvin\Ask\DataTransferObjects\Pinecone;

use Carbon\Carbon;
use Marvin\Ask\Abstracts\AbstractDataTransferObject;
use Marvin\Ask\Contracts\DtoContract;
use Marvin\Ask\Entities\VectorialDB\SearchMatch;

/**
 * @property float[] $values
 */
final class PineconeSearchMatch extends AbstractDataTransferObject implements DtoContract
{
    final public string $id {
        get => $this->id;
    }
    final public float $score {
        get => $this->score;
    }
    final public array $values {
        get => $this->values;
    }
    final Carbon $updatedAt {
        get => $this->updatedAt;
    }
    final string $pageUrl {
        get => $this->pageUrl;
    }
    final string $pageTitle {
        get => $this->pageTitle;
    }
    final string $content {
        get => $this->content;
    }
    // Aliases for backwards compatibility
    final string $title {
        get => $this->pageTitle;
    }
    final string $url {
        get => $this->pageUrl;
    }
    final string $text {
        get => $this->content;
    }

    public function __construct(
        string  $id,
        float   $score,
        array   $values,
        ?Carbon $updatedAt,
        string  $pageUrl,
        string  $pageTitle,
        string  $content
    )
    {
        $this->id = $id;
        $this->score = $score;
        $this->values = $values;
        $this->pageUrl = $pageUrl;
        $this->pageTitle = $pageTitle;
        $this->content = $content;
        $this->updatedAt = $updatedAt;
    }

    protected static function mapDataBeforeCreatingNewInstance(array $data): array
    {
        if (!isset($array['values'])) {
            $data['values'] = $data['vector'] ?? [];
        }

        if (isset($data['metadata'])) {
            $data['metadata']['updated_at'] = Carbon::parse($data['metadata']['updated_at']);
            $data = array_merge($data, $data['metadata']);
            unset($data['metadata']);
        }
        $data['updated_at'] = Carbon::parse($data['updated_at']);

        return $data;
    }

    public function toEntity(): SearchMatch
    {
        return new SearchMatch(
            id: $this->id,
            score: $this->score,
            vector: $this->values,
            updatedAt: $this->updatedAt,
            pageUrl: $this->pageUrl,
            pageTitle: $this->pageTitle,
            content: $this->content,
        );
    }
}
