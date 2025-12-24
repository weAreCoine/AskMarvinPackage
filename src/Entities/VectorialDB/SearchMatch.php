<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\VectorialDB;

use Carbon\Carbon;

final readonly class SearchMatch
{
    public function __construct(
        public string $id,
        public float $score,
        public array $vector,
        public ?Carbon $updatedAt,
        public string $pageUrl,
        public string $pageTitle,
        public string $content,
    ) {}

    public static function fromArray(array $data): SearchMatch
    {
        return new SearchMatch(
            id: $data['id'],
            score: $data['score'],
            vector: $data['vector'],
            updatedAt: $data['updatedAt'] ? Carbon::parse($data['updatedAt']) : null,
            pageUrl: $data['pageUrl'],
            pageTitle: $data['pageTitle'],
            content: $data['content'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'score' => $this->score,
            'vector' => $this->vector,
            'updated_at' => $this->updatedAt,
            'page_url' => $this->pageUrl,
            'page_title' => $this->pageTitle,
            'content' => $this->content,
        ];
    }
}
